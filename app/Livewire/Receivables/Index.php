<?php

namespace App\Livewire\Receivables;

use App\Enums\PaymentMethod;
use App\Enums\ReceivableStatus;
use App\Enums\TrafficLight;
use App\Models\AccountReceivable;
use App\Models\Client;
use App\Services\ReceivableService;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public ?int $clientFilter = null;

    public string $lightFilter = '';

    public string $statusFilter = 'abiertas';

    public bool $showPaymentForm = false;

    public ?int $payingId = null;

    public ?string $payment_date = null;

    public ?string $amount = null;

    public string $method = 'transferencia';

    public ?string $reference = null;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', Rule::in(array_column(PaymentMethod::cases(), 'value'))],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected array $validationAttributes = [
        'payment_date' => 'fecha de pago',
        'amount' => 'monto',
        'method' => 'medio de pago',
        'reference' => 'referencia',
    ];

    public function updating($property): void
    {
        if (in_array($property, ['clientFilter', 'lightFilter', 'statusFilter'], true)) {
            $this->resetPage();
        }
    }

    public function openPaymentForm(int $receivableId): void
    {
        $this->authorize('receivables.manage');

        $receivable = AccountReceivable::query()->findOrFail($receivableId);
        $this->payingId = $receivable->id;
        $this->payment_date = now()->format('Y-m-d');
        $this->amount = (string) round((float) $receivable->balance, 2);
        $this->method = 'transferencia';
        $this->reset(['reference', 'notes']);
        $this->resetValidation();
        $this->showPaymentForm = true;
    }

    public function savePayment(ReceivableService $service): void
    {
        $this->authorize('receivables.manage');
        $data = $this->validate();

        $receivable = AccountReceivable::query()->findOrFail($this->payingId);

        try {
            $service->registerPayment(
                $receivable,
                (float) $data['amount'],
                $data['payment_date'],
                $data['method'],
                $data['reference'],
                $data['notes'],
                auth()->user(),
            );
        } catch (\InvalidArgumentException $e) {
            $this->addError('amount', $e->getMessage());

            return;
        }

        $this->showPaymentForm = false;
        session()->now('ok', 'Pago registrado; el saldo y el semáforo se actualizaron.');
    }

    public function render(): View
    {
        $receivables = AccountReceivable::query()
            ->with(['invoice.client'])
            ->filter($this->clientFilter, $this->lightFilter ?: null)
            ->when($this->statusFilter === 'abiertas', fn ($q) => $q->open())
            ->when(
                $this->statusFilter !== '' && $this->statusFilter !== 'abiertas',
                fn ($q) => $q->where('status', $this->statusFilter),
            )
            ->orderBy('due_date')
            ->paginate(15);

        $openTotals = AccountReceivable::query()
            ->open()
            ->when($this->clientFilter, fn ($q) => $q->whereHas('invoice', fn ($i) => $i->where('client_id', $this->clientFilter)))
            ->selectRaw("traffic_light, COUNT(*) as cuenta, SUM(balance) as saldo")
            ->groupBy('traffic_light')
            ->get()
            ->keyBy(fn ($row) => $row->traffic_light->value);

        return view('livewire.receivables.index', [
            'receivables' => $receivables,
            'clients' => Client::query()->orderBy('business_name')->get(['id', 'business_name']),
            'lights' => TrafficLight::cases(),
            'statuses' => ReceivableStatus::cases(),
            'methods' => PaymentMethod::cases(),
            'openTotals' => $openTotals,
        ]);
    }
}
