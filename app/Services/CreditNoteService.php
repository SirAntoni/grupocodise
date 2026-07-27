<?php

namespace App\Services;

use App\Enums\CreditNoteStatus;
use App\Enums\InvoiceStatus;
use App\Enums\SeriesDocumentType;
use App\Enums\SunatStatus;
use App\Jobs\SubmitElectronicDocument;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreditNoteService
{
    public function __construct(
        protected NumberingService $numbering,
        protected ReceivableService $receivables,
    ) {}

    /**
     * Anula una factura aceptada emitiendo una nota de crédito por el total
     * (catálogo 09, motivo 01) y la encola hacia SUNAT.
     */
    public function annulInvoice(Invoice $invoice, string $motiveDescription, User $user): CreditNote
    {
        if (trim($motiveDescription) === '') {
            throw new InvalidArgumentException('El motivo de la anulación es obligatorio.');
        }

        return DB::transaction(function () use ($invoice, $motiveDescription, $user) {
            // Re-verificar bajo bloqueo: dos anulaciones concurrentes no deben
            // emitir dos notas de crédito por la misma factura.
            $invoice = Invoice::query()->whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            if ($invoice->status !== InvoiceStatus::Accepted) {
                throw new InvalidArgumentException('Solo se puede anular con nota de crédito una factura aceptada.');
            }

            if ($invoice->activeCreditNote()) {
                throw new InvalidArgumentException('La factura ya tiene una nota de crédito en curso.');
            }

            $numbering = $this->numbering->reserve(SeriesDocumentType::CreditNote);

            $creditNote = CreditNote::query()->create([
                'invoice_id' => $invoice->id,
                'affected_full_number' => $invoice->full_number,
                'currency' => $invoice->currency,
                'series_id' => $numbering['series']->id,
                'number' => $numbering['number'],
                'full_number' => $numbering['full_number'],
                'issue_date' => now()->toDateString(),
                'motive_code' => '01',
                'motive_description' => $motiveDescription,
                'taxable_amount' => $invoice->taxable_amount,
                'igv_rate' => config('facturacion.igv_rate') * 100,
                'igv' => $invoice->igv,
                'total' => $invoice->total,
                'status' => CreditNoteStatus::PendingSubmission,
                'created_by' => $user->id,
            ]);

            $document = $creditNote->electronicDocument()->create([
                'environment' => config('facturacion.environment'),
                'sunat_status' => SunatStatus::Pending,
            ]);

            SubmitElectronicDocument::dispatch($document)->afterCommit();

            return $creditNote;
        });
    }

    /**
     * Llamado por la cola cuando SUNAT acepta la NC: la factura queda anulada
     * y sus guías se liberan para refacturar (histórico intacto en el pivot).
     */
    public function markAccepted(CreditNote $creditNote): void
    {
        DB::transaction(function () use ($creditNote) {
            $creditNote->update(['status' => CreditNoteStatus::Accepted]);

            $invoice = $creditNote->invoice;
            $invoice->update([
                'status' => InvoiceStatus::Annulled,
                'annulled_at' => now(),
                'annulled_by' => $creditNote->created_by,
            ]);

            $invoice->dispatchGuides()->newPivotStatement()
                ->where('invoice_id', $invoice->id)
                ->update(['active' => null]);

            $this->receivables->annulForInvoice($invoice);
        });
    }

    /** Llamado por la cola cuando SUNAT rechaza la NC. */
    public function markRejected(CreditNote $creditNote): void
    {
        $creditNote->update(['status' => CreditNoteStatus::Rejected]);
    }

    /** Reenvía una NC rechazada o con error; misma numeración (idempotente). */
    public function resend(CreditNote $creditNote, User $user): void
    {
        $document = $creditNote->electronicDocument;

        if (! $document || $document->sunat_status->isAccepted()) {
            throw new InvalidArgumentException('La nota de crédito no está pendiente de corrección.');
        }

        if ($creditNote->status === CreditNoteStatus::Rejected) {
            $creditNote->update(['status' => CreditNoteStatus::PendingSubmission]);
        }

        $document->update(['sunat_status' => SunatStatus::Pending]);
        $document->logs()->create([
            'action' => 'enviar',
            'success' => true,
            'response_message' => 'Reenvío manual solicitado',
            'user_id' => $user->id,
        ]);

        SubmitElectronicDocument::dispatch($document);
    }
}
