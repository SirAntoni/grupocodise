<?php

namespace App\Livewire\PurchaseOrders;

use App\Enums\PurchaseOrderOrigin;
use App\Models\Client;
use App\Models\PurchaseOrder;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithFileUploads, WithPagination;

    public ?int $clientFilter = null;

    public string $originFilter = '';

    public bool $showForm = false;

    public ?int $client_id = null;

    public string $number = '';

    public ?string $date = null;

    public ?string $amount = null;

    public ?string $notes = null;

    public ?int $seller_id = null;

    public $pdf = null;

    protected function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'number' => [
                'required', 'string', 'max:30',
                Rule::unique('purchase_orders', 'number')->where('client_id', $this->client_id),
            ],
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'seller_id' => ['nullable', 'exists:users,id'],
            'pdf' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ];
    }

    protected array $validationAttributes = [
        'client_id' => 'cliente',
        'seller_id' => 'vendedor',
        'number' => 'número de OC',
        'date' => 'fecha',
        'amount' => 'monto',
        'pdf' => 'PDF adjunto',
    ];

    public function updating($property): void
    {
        if (in_array($property, ['clientFilter', 'originFilter'], true)) {
            $this->resetPage();
        }
    }

    public function openForm(): void
    {
        $this->authorize('purchase-orders.manage');
        $this->reset(['client_id', 'number', 'amount', 'notes', 'pdf']);
        $this->seller_id = auth()->id();
        $this->date = now()->format('Y-m-d');
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('purchase-orders.manage');
        $data = $this->validate();

        PurchaseOrder::query()->create([
            'client_id' => $data['client_id'],
            'origin' => PurchaseOrderOrigin::Received,
            'number' => $data['number'],
            'date' => $data['date'],
            'amount' => $data['amount'],
            'notes' => $data['notes'],
            'pdf_path' => $this->pdf?->store('ordenes-compra'),
            'seller_id' => $data['seller_id'] ?? null,
            'created_by' => auth()->id(),
        ]);

        $this->showForm = false;
        session()->now('ok', 'Orden de compra registrada.');
    }

    public function render(): View
    {
        $orders = PurchaseOrder::query()
            ->with(['client', 'quotation', 'seller'])
            ->withCount(['dispatchGuides', 'invoices'])
            ->when($this->clientFilter, fn ($q) => $q->where('client_id', $this->clientFilter))
            ->when($this->originFilter !== '', fn ($q) => $q->where('origin', $this->originFilter))
            ->latest('id')
            ->paginate(15);

        return view('livewire.purchase-orders.index', [
            'orders' => $orders,
            'clients' => Client::query()->orderBy('business_name')->get(['id', 'business_name']),
            'sellers' => \App\Models\User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
