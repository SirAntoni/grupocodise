<?php

namespace App\Livewire\Quotations;

use App\Enums\QuotationStatus;
use App\Models\Client;
use App\Models\Quotation;
use App\Services\QuotationService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public ?int $clientFilter = null;

    public string $statusFilter = '';

    public function updating($property): void
    {
        if (in_array($property, ['clientFilter', 'statusFilter'], true)) {
            $this->resetPage();
        }
    }

    public function accept(int $quotationId, QuotationService $service): void
    {
        $this->authorize('quotations.manage');

        try {
            $service->accept(Quotation::query()->findOrFail($quotationId), auth()->user());
            session()->now('ok', 'Cotización aceptada; ya puedes generar la orden de compra.');
        } catch (\InvalidArgumentException $e) {
            session()->now('error', $e->getMessage());
        }
    }

    public function reject(int $quotationId, QuotationService $service): void
    {
        $this->authorize('quotations.manage');

        try {
            $service->reject(Quotation::query()->findOrFail($quotationId), auth()->user());
            session()->now('ok', 'Cotización rechazada.');
        } catch (\InvalidArgumentException $e) {
            session()->now('error', $e->getMessage());
        }
    }

    public function render(): View
    {
        $quotations = Quotation::query()
            ->with(['client', 'purchaseOrder'])
            ->filter($this->clientFilter, $this->statusFilter ?: null)
            ->latest('id')
            ->paginate(15);

        return view('livewire.quotations.index', [
            'quotations' => $quotations,
            'clients' => Client::query()->orderBy('business_name')->get(['id', 'business_name']),
            'statuses' => QuotationStatus::cases(),
        ]);
    }
}
