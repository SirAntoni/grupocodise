<?php

namespace App\Livewire\Invoices;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $clientFilter = null;

    public string $statusFilter = '';

    public ?string $from = null;

    public ?string $until = null;

    public function updating($property): void
    {
        if (in_array($property, ['search', 'clientFilter', 'statusFilter', 'from', 'until'], true)) {
            $this->resetPage();
        }
    }

    public function render(): View
    {
        $invoices = Invoice::query()
            ->with(['client', 'electronicDocument', 'receivable'])
            ->when($this->search !== '', fn ($q) => $q->where('full_number', 'like', "%{$this->search}%"))
            ->filter($this->clientFilter, $this->statusFilter ?: null, $this->from, $this->until)
            ->latest('id')
            ->paginate(15);

        return view('livewire.invoices.index', [
            'invoices' => $invoices,
            'clients' => Client::query()->orderBy('business_name')->get(['id', 'business_name']),
            'statuses' => InvoiceStatus::cases(),
        ]);
    }
}
