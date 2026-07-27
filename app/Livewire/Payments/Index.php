<?php

namespace App\Livewire\Payments;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\ReceivableService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public ?int $clientFilter = null;

    public ?int $invoiceFilter = null;

    public ?string $from = null;

    public ?string $until = null;

    public bool $showDeleteForm = false;

    public ?int $deletingId = null;

    public string $deletion_reason = '';

    public function updating($property): void
    {
        if (in_array($property, ['clientFilter', 'invoiceFilter', 'from', 'until'], true)) {
            $this->resetPage();
        }

        // Al cambiar de cliente, una factura seleccionada de otro cliente
        // dejaría el listado filtrado por una opción invisible.
        if ($property === 'clientFilter') {
            $this->invoiceFilter = null;
        }
    }

    public function openDeleteForm(int $paymentId): void
    {
        $this->authorize('receivables.manage');
        $this->deletingId = $paymentId;
        $this->deletion_reason = '';
        $this->resetValidation();
        $this->showDeleteForm = true;
    }

    public function confirmDelete(ReceivableService $service): void
    {
        $this->authorize('receivables.manage');
        $this->validate(
            ['deletion_reason' => ['required', 'string', 'min:5', 'max:255']],
            [],
            ['deletion_reason' => 'motivo de anulación'],
        );

        $payment = Payment::query()->findOrFail($this->deletingId);

        try {
            $service->deletePayment($payment, $this->deletion_reason, auth()->user());
            session()->now('ok', 'Pago anulado; el saldo se recalculó.');
            $this->showDeleteForm = false;
        } catch (\InvalidArgumentException $e) {
            session()->now('error', $e->getMessage());
        }
    }

    public function render(): View
    {
        $payments = Payment::query()
            ->with(['receivable.invoice.client', 'createdBy'])
            ->filter($this->clientFilter, $this->invoiceFilter, $this->from, $this->until)
            ->latest('payment_date')
            ->latest('id')
            ->paginate(20);

        return view('livewire.payments.index', [
            'payments' => $payments,
            'clients' => Client::query()->orderBy('business_name')->get(['id', 'business_name']),
            'invoices' => Invoice::query()
                ->whereNotNull('full_number')
                ->when($this->clientFilter, fn ($q) => $q->where('client_id', $this->clientFilter))
                ->orderByDesc('id')
                ->get(['id', 'full_number']),
        ]);
    }
}
