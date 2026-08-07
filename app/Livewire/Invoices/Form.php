<?php

namespace App\Livewire\Invoices;

use App\Enums\InvoiceItemType;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?Invoice $invoice = null;

    /** Empresa a facturar (solo al crear: después la fija el borrador). */
    public ?int $clientId = null;

    /** Filtro por número dentro de la lista de guías disponibles. */
    public string $guideSearch = '';

    /** @var array<int, int> ids de las guías marcadas */
    public array $selectedGuides = [];

    public ?int $sellerId = null;

    public string $paymentType = 'credito';

    public ?int $creditDays = null;

    public bool $hasDetraction = false;

    public ?string $detractionCode = null;

    public ?int $purchaseOrderId = null;

    /** @var array<int, string> precios editables por invoice_item_id */
    public array $prices = [];

    public bool $hasRemoteZone = false;

    public ?string $remoteZoneAmount = null;

    public function mount(?Invoice $invoice = null): void
    {
        if ($invoice) {
            $this->invoice = $invoice->load(['client', 'items.product', 'dispatchGuides']);
            $this->authorize('update', $this->invoice);
            $this->clientId = $this->invoice->client_id;
            $this->sellerId = $this->invoice->seller_id;
            $this->paymentType = $this->invoice->payment_type;
            $this->creditDays = $this->invoice->credit_days ?: (int) config('facturacion.payment_due_days');
            $this->hasDetraction = (bool) $this->invoice->has_detraction;
            $this->detractionCode = $this->invoice->detraction_code ?: config('facturacion.detraccion.codigo_bien');
            $this->purchaseOrderId = $this->invoice->purchase_order_id;
            $this->fillFromInvoice();
        } else {
            $this->authorize('create', Invoice::class);
            $this->sellerId = auth()->id();
            $this->creditDays = (int) config('facturacion.payment_due_days');
            $this->detractionCode = config('facturacion.detraccion.codigo_bien');
        }
    }

    /** Cambiar de empresa vacía lo marcado: las guías son de otro cliente. */
    public function updatedClientId(): void
    {
        $this->selectedGuides = [];
        $this->guideSearch = '';
    }

    protected function fillFromInvoice(): void
    {
        $this->invoice->refresh()->load('items.product', 'dispatchGuides', 'client');

        $this->prices = $this->invoice->items
            ->where('type', InvoiceItemType::Product)
            ->mapWithKeys(fn ($item) => [$item->id => (string) round((float) $item->unit_value, 4)])
            ->all();

        $remoteZone = $this->invoice->items->firstWhere('type', InvoiceItemType::RemoteZone);
        $this->hasRemoteZone = $remoteZone !== null;
        $this->remoteZoneAmount = $remoteZone ? (string) round((float) $remoteZone->subtotal, 2) : null;
        $this->selectedGuides = [];
    }

    /** Crea el borrador con las guías marcadas. */
    public function createDraft(InvoiceService $service): void
    {
        $this->authorize('create', Invoice::class);
        $this->validate([
            'clientId' => ['required', 'exists:clients,id'],
            'selectedGuides' => ['required', 'array', 'min:1'],
            'sellerId' => ['nullable', 'exists:users,id'],
        ], [
            'selectedGuides.required' => 'Marca al menos una guía para facturar.',
            'selectedGuides.min' => 'Marca al menos una guía para facturar.',
        ], ['clientId' => 'empresa']);

        try {
            $invoice = $service->createDraftFromGuideIds($this->selectedGuides, auth()->user(), $this->clientId);
        } catch (\InvalidArgumentException $e) {
            $this->reconcileSelection($service);
            $this->addError('selectedGuides', $e->getMessage());

            return;
        }

        // Sin condición: elegir "Sin vendedor" debe poder dejarlo vacío.
        $invoice->update(['seller_id' => $this->sellerId]);

        session()->flash('ok', 'Borrador de factura creado; digita los precios y emite cuando esté listo.');
        $this->redirectRoute('facturas.editar', ['invoice' => $invoice->id], navigate: true);
    }

    /**
     * Descarta de la selección las guías que ya no están disponibles (otro
     * usuario pudo facturarlas mientras esta pantalla estaba abierta): si no,
     * la marca invisible bloquea el botón para siempre.
     */
    protected function reconcileSelection(InvoiceService $service): void
    {
        if ($this->selectedGuides === []) {
            return;
        }

        $clientId = $this->invoice?->client_id ?? $this->clientId;
        $disponibles = $clientId ? $service->availableGuidesFor($clientId)->pluck('id')->all() : [];

        $this->selectedGuides = array_values(array_intersect($this->selectedGuides, $disponibles));
    }

    public function addSelectedGuides(InvoiceService $service): void
    {
        $this->authorize('update', $this->invoice);
        $this->validate([
            'selectedGuides' => ['required', 'array', 'min:1'],
        ], [
            'selectedGuides.required' => 'Marca al menos una guía para agregar.',
            'selectedGuides.min' => 'Marca al menos una guía para agregar.',
        ]);

        try {
            $this->savePricing($service);
            $service->addGuideIds($this->invoice, $this->selectedGuides);
        } catch (\InvalidArgumentException $e) {
            $this->reconcileSelection($service);
            $this->addError('selectedGuides', $e->getMessage());

            return;
        }

        $this->fillFromInvoice();
        session()->now('ok', 'Guías agregadas y consolidadas.');
    }

    public function removeGuide(int $guideId, InvoiceService $service): void
    {
        $this->authorize('update', $this->invoice);

        $guide = $this->invoice->dispatchGuides()->findOrFail($guideId);

        try {
            $this->savePricing($service);
            $service->removeGuide($this->invoice, $guide);
        } catch (\InvalidArgumentException $e) {
            session()->now('error', $e->getMessage());

            return;
        }

        $this->fillFromInvoice();
        session()->now('ok', 'Guía retirada de la factura.');
    }

    public function save(InvoiceService $service): void
    {
        $this->authorize('update', $this->invoice);

        try {
            $this->savePricing($service);
            $this->saveSeller();
            $this->savePaymentTerms($service);
        } catch (\InvalidArgumentException $e) {
            session()->now('error', $e->getMessage());

            return;
        }

        $this->fillFromInvoice();
        session()->now('ok', 'Factura actualizada.');
    }

    public function issue(InvoiceService $service): void
    {
        $this->authorize('issue', $this->invoice);

        try {
            $this->savePricing($service);
            $this->saveSeller();
            $this->savePaymentTerms($service);
            $service->issue($this->invoice, auth()->user());
        } catch (\InvalidArgumentException $e) {
            session()->now('error', $e->getMessage());
            $this->fillFromInvoice();

            return;
        }

        session()->flash('ok', "Factura {$this->invoice->fresh()->full_number} emitida y en cola de envío a SUNAT.");
        $this->redirectRoute('facturas.ver', ['invoice' => $this->invoice->id], navigate: true);
    }

    /** Forma de pago, días de crédito, detracción y orden de compra. */
    protected function savePaymentTerms(InvoiceService $service): void
    {
        $this->validate([
            'paymentType' => ['required', 'in:contado,credito'],
            'creditDays' => [$this->paymentType === 'credito' ? 'required' : 'nullable', 'integer', 'min:1', 'max:365'],
            'hasDetraction' => ['boolean'],
            'detractionCode' => [$this->hasDetraction ? 'required' : 'nullable', 'string'],
            'purchaseOrderId' => ['nullable', 'exists:purchase_orders,id'],
        ], [], [
            'paymentType' => 'forma de pago',
            'creditDays' => 'días de crédito',
            'detractionCode' => 'bien o servicio de la detracción',
            'purchaseOrderId' => 'orden de compra',
        ]);

        $service->applyPaymentTerms(
            $this->invoice,
            $this->paymentType,
            $this->paymentType === 'credito' ? (int) $this->creditDays : null,
            $this->hasDetraction,
            $this->detractionCode,
        );

        $service->applyPurchaseOrder($this->invoice, $this->purchaseOrderId);
    }

    protected function saveSeller(): void
    {
        $this->validate(['sellerId' => ['nullable', 'exists:users,id']], [], ['sellerId' => 'vendedor']);

        if ($this->invoice->seller_id !== $this->sellerId) {
            $this->invoice->update(['seller_id' => $this->sellerId]);
        }
    }

    protected function savePricing(InvoiceService $service): void
    {
        $this->validate([
            'prices' => ['array'],
            'prices.*' => ['nullable', 'numeric', 'min:0'],
            'remoteZoneAmount' => [$this->hasRemoteZone ? 'required' : 'nullable', 'numeric', 'min:0'],
        ], [], [
            'prices.*' => 'precio unitario',
            'remoteZoneAmount' => 'monto de zona lejana',
        ]);

        $service->applyPricing(
            $this->invoice,
            // Un campo dejado en blanco NO pisa el precio ya guardado con 0.
            collect($this->prices)
                ->filter(fn ($v) => $v !== null && $v !== '')
                ->map(fn ($v) => (float) $v)
                ->all(),
            $this->hasRemoteZone ? (float) $this->remoteZoneAmount : null,
        );
    }

    public function render(InvoiceService $service): View
    {
        $clientId = $this->invoice?->client_id ?? $this->clientId;

        return view('livewire.invoices.form', [
            // Un cliente desactivado puede tener guías emitidas sin facturar:
            // si no apareciera, esas guías se quedarían sin poder facturarse.
            'clients' => Client::query()
                ->where(fn ($q) => $q->where('is_active', true)
                    ->orWhereHas('dispatchGuides', fn ($g) => $g->where('status', \App\Enums\DispatchGuideStatus::Issued)))
                ->orderBy('business_name')
                ->get(['id', 'business_name', 'ruc', 'is_active']),
            // Y el vendedor ya asignado se conserva aunque lo hayan desactivado.
            'sellers' => User::query()
                ->where(fn ($q) => $q->where('is_active', true)->orWhere('id', $this->sellerId))
                ->orderBy('name')
                ->get(['id', 'name', 'is_active']),
            'availableGuides' => $clientId ? $service->availableGuidesFor($clientId, trim($this->guideSearch)) : collect(),
            'detractionGoods' => \App\Support\SunatCatalogs::DETRACTION_GOODS,
            'purchaseOrders' => $this->invoice
                ? \App\Models\PurchaseOrder::query()->where('client_id', $this->invoice->client_id)->orderByDesc('date')->get(['id', 'number', 'date', 'amount'])
                : collect(),
        ]);
    }
}
