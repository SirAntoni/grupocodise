<?php

namespace App\Services;

use App\Enums\DispatchGuideStatus;
use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\SeriesDocumentType;
use App\Enums\SunatStatus;
use App\Jobs\SubmitElectronicDocument;
use App\Models\DispatchGuide;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InvoiceService
{
    public function __construct(
        protected NumberingService $numbering,
        protected ReceivableService $receivables,
    ) {}

    /**
     * Crea una factura en borrador consolidando una o varias guías emitidas
     * (referidas por su número completo, p. ej. "T001-00000012").
     *
     * @param  list<string>  $guideNumbers
     */
    public function createDraftFromGuideNumbers(array $guideNumbers, User $user): Invoice
    {
        return DB::transaction(function () use ($guideNumbers, $user) {
            $guides = $this->resolveGuides($guideNumbers);

            $clientIds = $guides->pluck('client_id')->unique();
            if ($clientIds->count() > 1) {
                throw new InvalidArgumentException('Todas las guías deben pertenecer al mismo cliente.');
            }

            $purchaseOrderIds = $guides->pluck('purchase_order_id')->filter()->unique();
            if ($purchaseOrderIds->count() > 1) {
                throw new InvalidArgumentException('Las guías consolidadas deben pertenecer a la misma orden de compra.');
            }

            $invoice = Invoice::query()->create([
                'client_id' => $clientIds->first(),
                'purchase_order_id' => $purchaseOrderIds->first(),
                'status' => InvoiceStatus::Draft,
                'payment_type' => 'credito',
                'created_by' => $user->id,
            ]);

            $this->attachGuides($invoice, $guides);
            $this->consolidateItems($invoice);

            return $invoice->refresh()->load('items', 'dispatchGuides');
        });
    }

    /**
     * Agrega una guía a un borrador y re-consolida conservando precios ya digitados.
     */
    public function addGuide(Invoice $invoice, string $guideNumber): Invoice
    {
        $this->assertEditable($invoice);

        return DB::transaction(function () use ($invoice, $guideNumber) {
            $guides = $this->resolveGuides([$guideNumber]);
            $guide = $guides->first();

            if ($guide->client_id !== $invoice->client_id) {
                throw new InvalidArgumentException('La guía pertenece a otro cliente.');
            }

            if ($guide->purchase_order_id && $invoice->purchase_order_id && $guide->purchase_order_id !== $invoice->purchase_order_id) {
                throw new InvalidArgumentException('La guía pertenece a otra orden de compra.');
            }

            $this->attachGuides($invoice, $guides);

            if (! $invoice->purchase_order_id && $guide->purchase_order_id) {
                $invoice->update(['purchase_order_id' => $guide->purchase_order_id]);
            }

            $this->consolidateItems($invoice);

            return $invoice->refresh()->load('items', 'dispatchGuides');
        });
    }

    public function removeGuide(Invoice $invoice, DispatchGuide $guide): Invoice
    {
        $this->assertEditable($invoice);

        return DB::transaction(function () use ($invoice, $guide) {
            $invoice->dispatchGuides()->detach($guide->id);
            $this->consolidateItems($invoice);

            return $invoice->refresh()->load('items', 'dispatchGuides');
        });
    }

    /**
     * Aplica precios editados y la línea opcional de zona lejana; recalcula
     * subtotal, IGV y total.
     *
     * @param  array<int, float>  $unitValuesByItemId  [invoice_item_id => valor unitario sin IGV]
     */
    public function applyPricing(Invoice $invoice, array $unitValuesByItemId, ?float $remoteZoneAmount): Invoice
    {
        $this->assertEditable($invoice);

        return DB::transaction(function () use ($invoice, $unitValuesByItemId, $remoteZoneAmount) {
            $rate = (float) config('facturacion.igv_rate');

            foreach ($invoice->items()->where('type', InvoiceItemType::Product)->get() as $item) {
                if (! array_key_exists($item->id, $unitValuesByItemId)) {
                    continue;
                }

                $unitValue = round((float) $unitValuesByItemId[$item->id], 4);
                $subtotal = round($unitValue * (float) $item->quantity, 2);
                $igv = round($subtotal * $rate, 2);

                $item->update([
                    'unit_value' => $unitValue,
                    'igv_rate' => $rate * 100,
                    'subtotal' => $subtotal,
                    'igv' => $igv,
                    'total' => round($subtotal + $igv, 2),
                ]);
            }

            $remoteZoneItem = $invoice->items()->where('type', InvoiceItemType::RemoteZone)->first();

            if ($remoteZoneAmount !== null && $remoteZoneAmount > 0) {
                $subtotal = round($remoteZoneAmount, 2);
                $igv = round($subtotal * $rate, 2);
                $payload = [
                    'type' => InvoiceItemType::RemoteZone,
                    'product_id' => null,
                    'description' => 'Recargo por zona lejana',
                    'unit_code' => 'ZZ',
                    'quantity' => 1,
                    'unit_value' => $subtotal,
                    'igv_rate' => $rate * 100,
                    'subtotal' => $subtotal,
                    'igv' => $igv,
                    'total' => round($subtotal + $igv, 2),
                ];

                $remoteZoneItem ? $remoteZoneItem->update($payload) : $invoice->items()->create($payload);
            } elseif ($remoteZoneItem) {
                $remoteZoneItem->delete();
            }

            $this->recalculateTotals($invoice);

            return $invoice->refresh()->load('items');
        });
    }

    /**
     * Emite la factura: numeración, vencimiento a 30 días y cola de envío a SUNAT.
     */
    public function issue(Invoice $invoice, User $user): Invoice
    {
        $this->assertEditable($invoice);

        if ($invoice->items()->count() === 0) {
            throw new InvalidArgumentException('La factura no tiene ítems.');
        }

        if ((float) $invoice->items()->sum('total') <= 0) {
            throw new InvalidArgumentException('El total debe ser mayor a cero; ingresa los precios de venta.');
        }

        if ($invoice->items()->where('type', InvoiceItemType::Product)->where('unit_value', '<=', 0)->exists()) {
            throw new InvalidArgumentException('Hay ítems sin precio de venta; digita todos los precios antes de emitir.');
        }

        if ($invoice->dispatchGuides()->count() === 0) {
            throw new InvalidArgumentException('La factura debe consolidar al menos una guía.');
        }

        return DB::transaction(function () use ($invoice, $user) {
            $numbering = $this->numbering->reserve(SeriesDocumentType::Invoice, $invoice->series_id);
            $issueDate = now()->toDateString();

            $invoice->update([
                'series_id' => $numbering['series']->id,
                'number' => $numbering['number'],
                'full_number' => $numbering['full_number'],
                'status' => InvoiceStatus::PendingSubmission,
                'issue_date' => $issueDate,
                'due_date' => now()->addDays((int) config('facturacion.payment_due_days'))->toDateString(),
                'issued_by' => $user->id,
            ]);

            $document = $invoice->electronicDocument()->create([
                'environment' => config('facturacion.environment'),
                'sunat_status' => SunatStatus::Pending,
            ]);

            SubmitElectronicDocument::dispatch($document)->afterCommit();

            return $invoice->refresh();
        });
    }

    /**
     * Reenvía a SUNAT una factura rechazada o con error, tras corregirla.
     * La numeración no cambia: el reenvío es idempotente.
     */
    public function resend(Invoice $invoice, User $user): void
    {
        $document = $invoice->electronicDocument;

        if (! $document || $document->sunat_status->isAccepted()) {
            throw new InvalidArgumentException('La factura no está pendiente de corrección.');
        }

        if ($invoice->status === InvoiceStatus::Rejected) {
            $invoice->update(['status' => InvoiceStatus::PendingSubmission]);
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

    /** Llamado por la cola cuando SUNAT acepta la factura. */
    public function markAccepted(Invoice $invoice): void
    {
        $invoice->update(['status' => InvoiceStatus::Accepted]);
        $this->receivables->createForInvoice($invoice->refresh());
    }

    /** Llamado por la cola cuando SUNAT rechaza la factura. */
    public function markRejected(Invoice $invoice): void
    {
        $invoice->update(['status' => InvoiceStatus::Rejected]);
    }

    public function deleteDraft(Invoice $invoice): void
    {
        $this->assertEditable($invoice);

        DB::transaction(function () use ($invoice) {
            $invoice->dispatchGuides()->detach();
            $invoice->items()->delete();
            $invoice->delete();
        });
    }

    /* ----------------------------------------------------------------- */

    protected function assertEditable(Invoice $invoice): void
    {
        if (! in_array($invoice->status, [InvoiceStatus::Draft, InvoiceStatus::Rejected], true)) {
            throw new InvalidArgumentException('Solo se puede modificar una factura en borrador o rechazada.');
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, DispatchGuide>
     */
    protected function resolveGuides(array $guideNumbers)
    {
        $numbers = collect($guideNumbers)->map(fn ($n) => mb_strtoupper(trim((string) $n)))->filter()->unique()->values();

        if ($numbers->isEmpty()) {
            throw new InvalidArgumentException('Ingresa al menos un número de guía.');
        }

        $guides = DispatchGuide::query()
            ->with(['items', 'electronicDocument'])
            ->whereIn('full_number', $numbers)
            ->lockForUpdate()
            ->get();

        $missing = $numbers->diff($guides->pluck('full_number'));
        if ($missing->isNotEmpty()) {
            throw new InvalidArgumentException('Guías no encontradas: '.$missing->implode(', ').'.');
        }

        foreach ($guides as $guide) {
            if ($guide->status !== DispatchGuideStatus::Issued) {
                throw new InvalidArgumentException("La guía {$guide->full_number} no está emitida.");
            }

            if ($guide->electronicDocument?->sunat_status === \App\Enums\SunatStatus::Rejected) {
                throw new InvalidArgumentException("La guía {$guide->full_number} fue rechazada por SUNAT; corrige y reenvía la GRE antes de facturarla.");
            }

            if ($active = $guide->activeInvoice()) {
                $reference = $active->full_number ?? "en borrador #{$active->id}";
                throw new InvalidArgumentException("La guía {$guide->full_number} ya está en la factura {$reference}.");
            }
        }

        return $guides;
    }

    protected function attachGuides(Invoice $invoice, $guides): void
    {
        try {
            $invoice->dispatchGuides()->attach(
                $guides->mapWithKeys(fn ($g) => [$g->id => ['active' => true]])->all(),
            );
        } catch (\Illuminate\Database\QueryException $e) {
            if ((string) $e->getCode() === '23000') {
                throw new InvalidArgumentException('Una de las guías ya está asociada a otra factura activa.');
            }

            throw $e;
        }
    }

    /**
     * Suma cantidades despachadas por producto de todas las guías asociadas.
     * Conserva el precio ya digitado para productos que se mantienen.
     */
    protected function consolidateItems(Invoice $invoice): void
    {
        $invoice->load('dispatchGuides.items');

        $existingValues = $invoice->items()
            ->where('type', InvoiceItemType::Product)
            ->pluck('unit_value', 'product_id');

        $rate = (float) config('facturacion.igv_rate');

        $consolidated = $invoice->dispatchGuides
            ->flatMap->items
            ->groupBy('product_id')
            ->map(fn ($items) => [
                'product_id' => $items->first()->product_id,
                'description' => $items->first()->description,
                'unit_code' => $items->first()->unit_code,
                'quantity' => (float) $items->sum('quantity_dispatched'),
            ])
            // SUNAT rechaza líneas con cantidad 0 (un producto solicitado
            // pero no despachado no se factura).
            ->filter(fn ($line) => $line['quantity'] > 0)
            ->values();

        $invoice->items()->where('type', InvoiceItemType::Product)->delete();

        foreach ($consolidated as $line) {
            $unitValue = (float) ($existingValues[$line['product_id']] ?? 0);
            $subtotal = round($unitValue * $line['quantity'], 2);
            $igv = round($subtotal * $rate, 2);

            $invoice->items()->create([
                'type' => InvoiceItemType::Product,
                'product_id' => $line['product_id'],
                'description' => $line['description'],
                'unit_code' => $line['unit_code'],
                'quantity' => $line['quantity'],
                'unit_value' => $unitValue,
                'igv_rate' => $rate * 100,
                'subtotal' => $subtotal,
                'igv' => $igv,
                'total' => round($subtotal + $igv, 2),
            ]);
        }

        $this->recalculateTotals($invoice);
    }

    protected function recalculateTotals(Invoice $invoice): void
    {
        $items = $invoice->items()->get();

        $invoice->update([
            'taxable_amount' => $items->sum('subtotal'),
            'igv' => $items->sum('igv'),
            'total' => $items->sum('total'),
        ]);
    }
}
