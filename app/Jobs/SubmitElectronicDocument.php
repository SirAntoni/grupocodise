<?php

namespace App\Jobs;

use App\Enums\SunatStatus;
use App\Models\CreditNote;
use App\Models\DispatchGuide;
use App\Models\ElectronicDocument;
use App\Models\Invoice;
use App\Services\CreditNoteService;
use App\Services\FacturacionElectronica;
use App\Services\Facturacion\SunatResult;
use App\Services\InvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Envía un comprobante a SUNAT en segundo plano con reintentos.
 * Idempotente: la numeración se asignó ANTES de encolar y nunca se
 * regenera; reintentar solo reenvía el mismo documento.
 */
class SubmitElectronicDocument implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * El sondeo del ticket GRE usa release(), que cuenta como intento; por eso
     * el límite es de TIEMPO (retryUntil) y no de intentos, con un tope solo
     * para excepciones reales.
     */
    public int $maxExceptions = 5;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900, 1800];

    public function __construct(public ElectronicDocument $document) {}

    public function retryUntil(): \DateTime
    {
        return now()->addHours(2);
    }

    public function uniqueId(): string
    {
        return (string) $this->document->id;
    }

    /**
     * Evita que el worker y un "procesar ahora" síncrono envíen el mismo
     * comprobante en paralelo (el driver sync también ejecuta middleware).
     */
    public function middleware(): array
    {
        return [
            (new \Illuminate\Queue\Middleware\WithoutOverlapping('sunat-doc-'.$this->document->id))
                ->expireAfter(180)
                ->releaseAfter(30),
        ];
    }

    public function handle(FacturacionElectronica $sunat, InvoiceService $invoices, CreditNoteService $creditNotes): void
    {
        $this->document->refresh();
        $documentable = $this->document->documentable;

        if ($documentable === null || $this->document->sunat_status->isAccepted()) {
            return;
        }

        $this->document->increment('attempts');

        try {
            $result = match (true) {
                $documentable instanceof DispatchGuide && $this->document->ticket !== null => $sunat->checkDispatchGuideStatus($documentable, $this->document->ticket),
                $documentable instanceof DispatchGuide => $sunat->sendDispatchGuide($documentable),
                $documentable instanceof Invoice => $sunat->sendInvoice($documentable->load('items.product', 'client', 'series')),
                $documentable instanceof CreditNote => $sunat->sendCreditNote($documentable->load('invoice.items.product', 'invoice.client', 'series')),
                default => throw new \LogicException('Tipo de documento no soportado: '.get_class($documentable)),
            };
        } catch (\LogicException $e) {
            throw $e;
        } catch (\Throwable $e) {
            // Falla de comunicación o del servicio: registrar y reintentar.
            $result = SunatResult::error(null, $e->getMessage());
        }

        $this->persistArtifacts($result, $documentable->full_number);

        // El código que devuelve SUNAT no siempre es el "0160" de manual: ante
        // un fault genérico, Greenter arma el código concatenando los dígitos
        // del mensaje y sale mucho más largo que la columna. Se recorta para
        // guardarlo; el motivo íntegro queda en el mensaje, que es TEXT.
        $code = $result->code === null ? null : mb_substr($result->code, 0, 20);

        $this->document->logs()->create([
            'action' => $this->document->ticket && $documentable instanceof DispatchGuide ? 'consultar_ticket' : 'enviar',
            'success' => $result->success && ($result->accepted || $result->inProgress),
            'response_code' => $code,
            'response_message' => $result->message,
        ]);

        if ($result->inProgress) {
            // GRE en proceso: guardar ticket y reintentar la consulta luego.
            $this->document->update([
                'sunat_status' => SunatStatus::Sent,
                'ticket' => $result->ticket,
                'error_code' => null,
                'error_message' => null,
                'sent_at' => $this->document->sent_at ?? now(),
            ]);
            $this->release(30);

            return;
        }

        if (! $result->success) {
            // Falla de comunicación/servicio: dejar en error y reintentar.
            $this->document->update([
                'sunat_status' => SunatStatus::Error,
                'error_code' => $code,
                'error_message' => $result->message,
            ]);

            $this->release($this->backoff[min($this->attempts() - 1, count($this->backoff) - 1)]);

            return;
        }

        if ($result->accepted) {
            $this->document->update([
                'sunat_status' => $result->hasObservations ? SunatStatus::AcceptedWithObservations : SunatStatus::Accepted,
                'error_code' => null,
                'error_message' => null,
                'sent_at' => $this->document->sent_at ?? now(),
                'accepted_at' => now(),
                'digest_hash' => $result->hash ?? $this->document->digest_hash,
            ]);

            if ($documentable instanceof Invoice) {
                $invoices->markAccepted($documentable);
            } elseif ($documentable instanceof CreditNote) {
                $creditNotes->markAccepted($documentable);
            }

            return;
        }

        // Rechazado por SUNAT: persistir motivo para corregir y reenviar.
        $this->document->update([
            'sunat_status' => SunatStatus::Rejected,
            'error_code' => $code,
            'error_message' => $result->message,
            'sent_at' => $this->document->sent_at ?? now(),
        ]);

        if ($documentable instanceof Invoice) {
            $invoices->markRejected($documentable);
        } elseif ($documentable instanceof CreditNote) {
            $creditNotes->markRejected($documentable);
        }
    }

    protected function persistArtifacts(SunatResult $result, ?string $fullNumber): void
    {
        $base = 'sunat/'.now()->format('Y/m');
        $name = $fullNumber ?? 'doc-'.$this->document->id;

        if ($result->signedXml) {
            $path = "{$base}/{$name}.xml";
            Storage::put($path, $result->signedXml);
            $this->document->xml_path = $path;
        }

        if ($result->cdrZip) {
            $path = "{$base}/R-{$name}.zip";
            Storage::put($path, $result->cdrZip);
            $this->document->cdr_path = $path;
        }

        if ($result->hash) {
            $this->document->digest_hash = $result->hash;
        }

        $this->document->save();
    }

    public function failed(\Throwable $exception): void
    {
        $this->document->refresh();

        // Nunca degradar un comprobante ya aceptado; y si hay ticket vigente,
        // conservar "enviado" para poder reconsultarlo manualmente.
        if ($this->document->sunat_status->isAccepted()) {
            return;
        }

        $this->document->update([
            'sunat_status' => $this->document->ticket ? SunatStatus::Sent : SunatStatus::Error,
            'error_message' => 'Reintentos agotados: '.$exception->getMessage(),
        ]);
    }
}
