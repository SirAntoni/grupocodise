<?php

namespace App\Livewire\Quotations;

use App\Models\Quotation;
use App\Services\QuotationService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public Quotation $quotation;

    public function mount(int $quotation): void
    {
        $this->authorize('quotations.view');

        $this->quotation = Quotation::query()
            ->with(['client', 'items.product', 'purchaseOrder', 'createdBy', 'statusChangedBy'])
            ->findOrFail($quotation);
    }

    public function accept(QuotationService $service): void
    {
        $this->authorize('quotations.manage');

        try {
            $service->accept($this->quotation, auth()->user());
            session()->now('ok', 'Cotización aceptada.');
        } catch (\InvalidArgumentException $e) {
            session()->now('error', $e->getMessage());
        }

        $this->quotation->refresh();
    }

    public function reject(QuotationService $service): void
    {
        $this->authorize('quotations.manage');

        try {
            $service->reject($this->quotation, auth()->user());
            session()->now('ok', 'Cotización rechazada.');
        } catch (\InvalidArgumentException $e) {
            session()->now('error', $e->getMessage());
        }

        $this->quotation->refresh();
    }

    public function generatePurchaseOrder(QuotationService $service): void
    {
        $this->authorize('purchase-orders.manage');

        try {
            $po = $service->generatePurchaseOrder($this->quotation, auth()->user());
            session()->flash('ok', "Orden de compra {$po->number} generada.");
            $this->redirectRoute('ordenes-compra.index', navigate: true);
        } catch (\InvalidArgumentException $e) {
            session()->now('error', $e->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.quotations.show');
    }
}
