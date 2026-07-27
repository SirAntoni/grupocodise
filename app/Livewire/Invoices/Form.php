<?php

namespace App\Livewire\Invoices;

use App\Enums\InvoiceItemType;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?Invoice $invoice = null;

    /** Número de guía a agregar (modo creación y edición). */
    public string $guideNumber = '';

    /** @var array<int, string> precios editables por invoice_item_id */
    public array $prices = [];

    public bool $hasRemoteZone = false;

    public ?string $remoteZoneAmount = null;

    public function mount(?Invoice $invoice = null): void
    {
        if ($invoice) {
            $this->invoice = $invoice->load(['client', 'items.product', 'dispatchGuides']);
            $this->authorize('update', $this->invoice);
            $this->fillFromInvoice();
        } else {
            $this->authorize('create', Invoice::class);
        }
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
    }

    /** Crea el borrador con la primera guía digitada. */
    public function createDraft(InvoiceService $service): void
    {
        $this->authorize('create', Invoice::class);
        $this->validate(['guideNumber' => ['required', 'string']], [], ['guideNumber' => 'número de guía']);

        try {
            $invoice = $service->createDraftFromGuideNumbers([$this->guideNumber], auth()->user());
        } catch (\InvalidArgumentException $e) {
            $this->addError('guideNumber', $e->getMessage());

            return;
        }

        session()->flash('ok', 'Borrador de factura creado; agrega más guías y digita los precios.');
        $this->redirectRoute('facturas.editar', ['invoice' => $invoice->id], navigate: true);
    }

    public function addGuide(InvoiceService $service): void
    {
        $this->authorize('update', $this->invoice);
        $this->validate(['guideNumber' => ['required', 'string']], [], ['guideNumber' => 'número de guía']);

        try {
            $this->savePricing($service);
            $service->addGuide($this->invoice, $this->guideNumber);
        } catch (\InvalidArgumentException $e) {
            $this->addError('guideNumber', $e->getMessage());

            return;
        }

        $this->guideNumber = '';
        $this->fillFromInvoice();
        session()->now('ok', 'Guía agregada y consolidada.');
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
        } catch (\InvalidArgumentException $e) {
            session()->now('error', $e->getMessage());

            return;
        }

        $this->fillFromInvoice();
        session()->now('ok', 'Precios y totales actualizados.');
    }

    public function issue(InvoiceService $service): void
    {
        $this->authorize('issue', $this->invoice);

        try {
            $this->savePricing($service);
            $service->issue($this->invoice, auth()->user());
        } catch (\InvalidArgumentException $e) {
            session()->now('error', $e->getMessage());
            $this->fillFromInvoice();

            return;
        }

        session()->flash('ok', "Factura {$this->invoice->fresh()->full_number} emitida y en cola de envío a SUNAT.");
        $this->redirectRoute('facturas.ver', ['invoice' => $this->invoice->id], navigate: true);
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

    public function render(): View
    {
        return view('livewire.invoices.form');
    }
}
