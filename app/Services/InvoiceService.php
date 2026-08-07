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
use App\Support\SunatCatalogs;
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
        return DB::transaction(fn () => $this->createDraft($this->resolveGuides($guideNumbers), $user));
    }

    /**
     * Igual que la anterior, pero con las guías marcadas en pantalla.
     *
     * @param  list<int>  $guideIds
     */
    public function createDraftFromGuideIds(
        array $guideIds,
        User $user,
        ?int $expectedClientId = null,
        SeriesDocumentType $documentType = SeriesDocumentType::Invoice,
    ): Invoice {
        return DB::transaction(fn () => $this->createDraft($this->resolveGuidesByIds($guideIds), $user, $expectedClientId, $documentType));
    }

    /**
     * Guías emitidas de un cliente que todavía se pueden facturar: sin factura
     * activa encima y sin rechazo de SUNAT pendiente de corregir.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, DispatchGuide>
     */
    public function availableGuidesFor(int $clientId, string $search = '', int $limit = 100)
    {
        return DispatchGuide::query()
            ->with('requirement')
            // Solo se factura lo despachado, así que ese es el conteo útil.
            ->withCount('dispatchedItems')
            ->where('client_id', $clientId)
            ->where('status', DispatchGuideStatus::Issued)
            ->whereDoesntHave('electronicDocument', fn ($q) => $q->where('sunat_status', SunatStatus::Rejected))
            ->whereDoesntHave('invoices', fn ($q) => $q->whereIn('invoices.status', InvoiceStatus::activeStates()))
            ->when($search !== '', fn ($q) => $q->where('full_number', 'like', '%'.$search.'%'))
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    protected function createDraft($guides, User $user, ?int $expectedClientId = null, SeriesDocumentType $documentType = SeriesDocumentType::Invoice): Invoice
    {
        $clientIds = $guides->pluck('client_id')->unique();
        if ($clientIds->count() > 1) {
            throw new InvalidArgumentException('Todas las guías deben pertenecer al mismo cliente.');
        }

        // Los ids llegan del navegador: la factura debe salir a nombre de la
        // empresa que el usuario vio en pantalla, no de la que digan las guías.
        if ($expectedClientId !== null && (int) $clientIds->first() !== (int) $expectedClientId) {
            throw new InvalidArgumentException('Las guías marcadas no son de la empresa elegida.');
        }

        $purchaseOrderIds = $guides->pluck('purchase_order_id')->filter()->unique();
        if ($purchaseOrderIds->count() > 1) {
            throw new InvalidArgumentException('Las guías consolidadas deben pertenecer a la misma orden de compra.');
        }

        $invoice = Invoice::query()->create([
            'client_id' => $clientIds->first(),
            'document_type' => $documentType,
            'purchase_order_id' => $purchaseOrderIds->first(),
            'status' => InvoiceStatus::Draft,
            'payment_type' => 'credito',
            // El vendedor se propone con quien registra y se puede cambiar.
            'seller_id' => $user->id,
            'created_by' => $user->id,
        ]);

        $this->attachGuides($invoice, $guides);
        $this->consolidateItems($invoice);

        return $invoice->refresh()->load('items', 'dispatchGuides');
    }

    /**
     * Agrega una guía a un borrador y re-consolida conservando precios ya digitados.
     */
    public function addGuide(Invoice $invoice, string $guideNumber): Invoice
    {
        $this->assertEditable($invoice);

        return DB::transaction(fn () => $this->attachToDraft($invoice, $this->resolveGuides([$guideNumber])));
    }

    /**
     * Agrega al borrador las guías marcadas en pantalla.
     *
     * @param  list<int>  $guideIds
     */
    public function addGuideIds(Invoice $invoice, array $guideIds): Invoice
    {
        $this->assertEditable($invoice);

        return DB::transaction(fn () => $this->attachToDraft($invoice, $this->resolveGuidesByIds($guideIds)));
    }

    protected function attachToDraft(Invoice $invoice, $guides): Invoice
    {
        foreach ($guides as $guide) {
            if ($guide->client_id !== $invoice->client_id) {
                throw new InvalidArgumentException("La guía {$guide->full_number} pertenece a otro cliente.");
            }

            if ($guide->purchase_order_id && $invoice->purchase_order_id && $guide->purchase_order_id !== $invoice->purchase_order_id) {
                throw new InvalidArgumentException("La guía {$guide->full_number} pertenece a otra orden de compra.");
            }
        }

        $purchaseOrderIds = $guides->pluck('purchase_order_id')->filter()->unique();
        if ($purchaseOrderIds->count() > 1) {
            throw new InvalidArgumentException('Las guías marcadas pertenecen a órdenes de compra distintas.');
        }

        $this->attachGuides($invoice, $guides);

        if (! $invoice->purchase_order_id && $purchaseOrderIds->isNotEmpty()) {
            $invoice->update(['purchase_order_id' => $purchaseOrderIds->first()]);
        }

        $this->consolidateItems($invoice);

        return $invoice->refresh()->load('items', 'dispatchGuides');
    }

    public function removeGuide(Invoice $invoice, DispatchGuide $guide): Invoice
    {
        $this->assertEditable($invoice);

        return DB::transaction(function () use ($invoice, $guide) {
            $invoice->dispatchGuides()->detach($guide->id);

            // La OC se hereda de las guías: si se retira la que la traía, la
            // factura no puede quedarse con una orden que ya nadie respalda.
            $invoice->update([
                'purchase_order_id' => $invoice->dispatchGuides()->pluck('purchase_order_id')->filter()->unique()->first(),
            ]);

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

            // Si cambió el total, la detracción tiene que seguirlo.
            $this->refreshDetraction($invoice->refresh());

            return $invoice->refresh()->load('items');
        });
    }

    /**
     * Condiciones de pago de la factura: contado o crédito con sus días, y si
     * la operación está sujeta a detracción.
     */
    public function applyPaymentTerms(
        Invoice $invoice,
        string $paymentType,
        ?int $creditDays,
        bool $hasDetraction,
        ?string $detractionCode,
    ): Invoice {
        $this->assertEditable($invoice);

        if (! in_array($paymentType, ['contado', 'credito'], true)) {
            throw new InvalidArgumentException('La forma de pago debe ser contado o crédito.');
        }

        if ($paymentType === 'credito' && ($creditDays === null || $creditDays < 1)) {
            throw new InvalidArgumentException('Indica a cuántos días es el crédito.');
        }

        $codigo = $detractionCode ?: config('facturacion.detraccion.codigo_bien');

        if ($hasDetraction && SunatCatalogs::detractionPercent($codigo) === null) {
            throw new InvalidArgumentException('Elige el bien o servicio que origina la detracción.');
        }

        $invoice->update([
            'payment_type' => $paymentType,
            'credit_days' => $paymentType === 'credito' ? $creditDays : null,
            'has_detraction' => $hasDetraction,
            'detraction_code' => $hasDetraction ? $codigo : null,
            'detraction_percent' => $hasDetraction ? SunatCatalogs::detractionPercent($codigo) : null,
        ]);

        return $this->refreshDetraction($invoice->refresh());
    }

    /**
     * Reglas de SUNAT sobre a quién se le puede emitir cada comprobante: la
     * factura exige RUC, y la boleta solo puede ir sin identificar al comprador
     * por debajo del monto que fija SUNAT (hoy S/ 700).
     */
    protected function assertClientMatchesDocumentType(Invoice $invoice): void
    {
        $cliente = $invoice->client;

        if ($invoice->document_type === SeriesDocumentType::Invoice && $cliente->document_type !== '6') {
            throw new InvalidArgumentException('Una factura solo puede emitirse a un cliente con RUC; para una persona usa boleta de venta.');
        }

        if ($invoice->document_type === SeriesDocumentType::Receipt
            && $cliente->document_type === '0'
            && (float) $invoice->total > (float) config('facturacion.boleta.monto_sin_documento')) {
            $tope = number_format((float) config('facturacion.boleta.monto_sin_documento'), 0);

            throw new InvalidArgumentException("Sobre S/ {$tope} la boleta debe identificar al comprador: registra su DNI antes de emitir.");
        }
    }

    /**
     * Vincula (o desvincula) la orden de compra del cliente. Se hereda de las
     * guías, pero al facturar se puede corregir a mano.
     */
    public function applyPurchaseOrder(Invoice $invoice, ?int $purchaseOrderId): Invoice
    {
        $this->assertEditable($invoice);

        if ($purchaseOrderId !== null) {
            $orden = \App\Models\PurchaseOrder::query()->find($purchaseOrderId);

            if (! $orden || $orden->client_id !== $invoice->client_id) {
                throw new InvalidArgumentException('La orden de compra es de otro cliente.');
            }
        }

        $invoice->update(['purchase_order_id' => $purchaseOrderId]);

        return $invoice->refresh();
    }

    /**
     * Recalcula el monto de la detracción sobre el total vigente.
     *
     * SUNAT deposita en soles enteros, así que el monto se redondea al sol
     * más cercano (una base de 1 840.80 al 10 % deposita 184, no 184.08).
     */
    public function refreshDetraction(Invoice $invoice): Invoice
    {
        if (! $invoice->has_detraction) {
            $invoice->update(['detraction_amount' => null]);

            return $invoice->refresh();
        }

        $invoice->update([
            'detraction_amount' => round((float) $invoice->total * (float) $invoice->detraction_percent / 100),
        ]);

        return $invoice->refresh();
    }

    /**
     * Emite la factura: numeración, vencimiento según sus días de crédito y
     * cola de envío a SUNAT.
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
            throw new InvalidArgumentException('El comprobante debe consolidar al menos una guía.');
        }

        $this->assertClientMatchesDocumentType($invoice);

        return DB::transaction(function () use ($invoice, $user) {
            $numbering = $this->numbering->reserve($invoice->document_type, $invoice->series_id);
            $issueDate = now();

            // Al contado vence el mismo día; al crédito, a los días pactados en
            // esta factura (no al ajuste global, que solo sirve de propuesta).
            $creditDays = $invoice->payment_type === 'credito'
                ? (int) ($invoice->credit_days ?: config('facturacion.payment_due_days'))
                : 0;

            $invoice->update([
                'series_id' => $numbering['series']->id,
                'number' => $numbering['number'],
                'full_number' => $numbering['full_number'],
                'status' => InvoiceStatus::PendingSubmission,
                'issue_date' => $issueDate->toDateString(),
                'credit_days' => $invoice->payment_type === 'credito' ? $creditDays : null,
                'due_date' => $issueDate->copy()->addDays($creditDays)->toDateString(),
                'issued_by' => $user->id,
            ]);

            // El monto de la detracción se congela con el total definitivo.
            $this->refreshDetraction($invoice->refresh());

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

        $document->update(['sunat_status' => SunatStatus::Pending, 'attempts' => 0]);
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

        // Al contado no hay nada que cobrar después: el tablero de cobranza es
        // para el seguimiento de las facturas al crédito.
        if ($invoice->payment_type === 'credito') {
            $this->receivables->createForInvoice($invoice->refresh());
        }
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

        $this->assertInvoiceable($guides);

        return $guides;
    }

    /**
     * Igual, pero por id: es lo que llega cuando se marcan en pantalla.
     *
     * @param  list<int>  $guideIds
     */
    protected function resolveGuidesByIds(array $guideIds)
    {
        $ids = collect($guideIds)->filter()->map(fn ($id) => (int) $id)->unique()->values();

        if ($ids->isEmpty()) {
            throw new InvalidArgumentException('Marca al menos una guía.');
        }

        $guides = DispatchGuide::query()
            ->with(['items', 'electronicDocument'])
            ->whereIn('id', $ids)
            ->lockForUpdate()
            ->get();

        if ($guides->count() !== $ids->count()) {
            throw new InvalidArgumentException('Alguna de las guías marcadas ya no existe.');
        }

        $this->assertInvoiceable($guides);

        return $guides;
    }

    /**
     * Una guía solo se factura si está emitida, sin rechazo de SUNAT pendiente
     * y sin otra factura activa encima.
     */
    protected function assertInvoiceable($guides): void
    {
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
