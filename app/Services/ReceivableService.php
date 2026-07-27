<?php

namespace App\Services;

use App\Enums\ReceivableStatus;
use App\Enums\TrafficLight;
use App\Models\AccountReceivable;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReceivableService
{
    /**
     * Toda factura aceptada genera su cuenta por cobrar con vencimiento a 30 días.
     */
    public function createForInvoice(Invoice $invoice): AccountReceivable
    {
        return AccountReceivable::query()->firstOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'due_date' => $invoice->due_date,
                'amount' => $invoice->total,
                'paid_amount' => 0,
                'balance' => $invoice->total,
                'traffic_light' => TrafficLight::forDueDate($invoice->due_date),
                'status' => ReceivableStatus::Pending,
            ],
        );
    }

    public function annulForInvoice(Invoice $invoice): void
    {
        $invoice->receivable?->update(['status' => ReceivableStatus::Annulled]);
    }

    /**
     * Registra un pago parcial o total y actualiza el saldo.
     */
    public function registerPayment(
        AccountReceivable $receivable,
        float $amount,
        string $paymentDate,
        string $method,
        ?string $reference,
        ?string $notes,
        User $user,
    ): Payment {
        if ($amount <= 0) {
            throw new InvalidArgumentException('El monto del pago debe ser mayor a cero.');
        }

        return DB::transaction(function () use ($receivable, $amount, $paymentDate, $method, $reference, $notes, $user) {
            // Bloqueo pesimista: dos pagos concurrentes no pueden leer el
            // mismo saldo y sobrepagar la cuenta.
            /** @var AccountReceivable $locked */
            $locked = AccountReceivable::query()->whereKey($receivable->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isOpen()) {
                throw new InvalidArgumentException('La cuenta por cobrar no está abierta.');
            }

            if (round($amount, 2) > round((float) $locked->balance, 2)) {
                throw new InvalidArgumentException(
                    'El pago (S/ '.number_format($amount, 2).') excede el saldo pendiente (S/ '.number_format((float) $locked->balance, 2).').',
                );
            }

            $payment = $locked->payments()->create([
                'payment_date' => $paymentDate,
                'amount' => $amount,
                'method' => $method,
                'reference' => $reference,
                'notes' => $notes,
                'created_by' => $user->id,
            ]);

            $this->recalculate($locked);
            $receivable->refresh();

            return $payment;
        });
    }

    /**
     * Anula un pago registrado por error, dejando quién y por qué.
     */
    public function deletePayment(Payment $payment, string $reason, User $user): void
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('El motivo de anulación del pago es obligatorio.');
        }

        DB::transaction(function () use ($payment, $reason, $user) {
            $locked = AccountReceivable::query()->whereKey($payment->account_receivable_id)->lockForUpdate()->firstOrFail();

            $payment->update([
                'deletion_reason' => $reason,
                'deleted_by' => $user->id,
            ]);
            $payment->delete();

            $this->recalculate($locked);
        });
    }

    /**
     * Recalcula saldo, estado y semáforo de una cuenta.
     */
    public function recalculate(AccountReceivable $receivable): void
    {
        $receivable->refresh();

        if ($receivable->status === ReceivableStatus::Annulled) {
            return;
        }

        $paid = (float) $receivable->payments()->sum('amount');
        $balance = round((float) $receivable->amount - $paid, 2);

        $receivable->update([
            'paid_amount' => $paid,
            'balance' => $balance,
            'status' => $balance <= 0
                ? ReceivableStatus::Paid
                : ($paid > 0 ? ReceivableStatus::Partial : ReceivableStatus::Pending),
            'traffic_light' => TrafficLight::forDueDate($receivable->due_date),
        ]);
    }

    /**
     * Recalcula el semáforo de todas las cuentas abiertas (scheduler diario).
     *
     * @return int cantidad de cuentas actualizadas
     */
    public function refreshTrafficLights(): int
    {
        $count = 0;

        AccountReceivable::query()->open()->chunkById(200, function ($receivables) use (&$count) {
            foreach ($receivables as $receivable) {
                $light = TrafficLight::forDueDate($receivable->due_date);

                if ($receivable->traffic_light !== $light) {
                    $receivable->update(['traffic_light' => $light]);
                    $count++;
                }
            }
        });

        return $count;
    }
}
