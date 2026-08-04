<?php

namespace App\Services;

use App\Enums\PurchaseOrderOrigin;
use App\Enums\QuotationStatus;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class QuotationService
{
    /**
     * Crea o actualiza una cotización (editable solo en estado enviada).
     *
     * @param  array{client_id: int, issue_date: string, valid_until: string, notes: ?string}  $data
     * @param  list<array{product_id: ?int, description: string, unit_code: string, quantity: float, unit_value: float}>  $items
     */
    public function save(?Quotation $quotation, array $data, array $items, User $user): Quotation
    {
        if ($quotation && ! $quotation->isEditable()) {
            throw new InvalidArgumentException('Solo se puede editar una cotización en estado enviada.');
        }

        return DB::transaction(function () use ($quotation, $data, $items, $user) {
            $rate = (float) config('facturacion.igv_rate');

            if ($quotation) {
                $quotation->update($data);
                $quotation->items()->delete();
            } else {
                // Código derivado del id: sin carreras contra el unique.
                $quotation = Quotation::query()->create([
                    ...$data,
                    'code' => Quotation::temporaryCode(),
                    'status' => QuotationStatus::Sent,
                    'created_by' => $user->id,
                ]);
                $quotation->update(['code' => sprintf('COT-%05d', $quotation->id)]);
            }

            $taxable = 0.0;
            foreach ($items as $item) {
                $subtotal = round((float) $item['quantity'] * (float) $item['unit_value'], 2);
                $taxable += $subtotal;

                $quotation->items()->create([
                    'product_id' => $item['product_id'] ?: null,
                    'description' => $item['description'],
                    'unit_code' => $item['unit_code'],
                    'quantity' => $item['quantity'],
                    'unit_value' => $item['unit_value'],
                    'subtotal' => $subtotal,
                ]);
            }

            $igv = round($taxable * $rate, 2);
            $quotation->update([
                'taxable_amount' => round($taxable, 2),
                'igv' => $igv,
                'total' => round($taxable + $igv, 2),
            ]);

            return $quotation->refresh();
        });
    }

    public function accept(Quotation $quotation, User $user): void
    {
        $this->transition($quotation, QuotationStatus::Accepted, $user);
    }

    public function reject(Quotation $quotation, User $user): void
    {
        $this->transition($quotation, QuotationStatus::Rejected, $user);
    }

    protected function transition(Quotation $quotation, QuotationStatus $status, User $user): void
    {
        if ($quotation->status !== QuotationStatus::Sent) {
            throw new InvalidArgumentException('La cotización ya fue resuelta.');
        }

        $quotation->update([
            'status' => $status,
            'status_changed_at' => now(),
            'status_changed_by' => $user->id,
        ]);
    }

    /**
     * Genera la orden de compra desde una cotización aceptada, con los datos precargados.
     */
    public function generatePurchaseOrder(Quotation $quotation, User $user): PurchaseOrder
    {
        if ($quotation->status !== QuotationStatus::Accepted) {
            throw new InvalidArgumentException('Solo se puede generar la OC de una cotización aceptada.');
        }

        if ($quotation->purchaseOrder) {
            throw new InvalidArgumentException("La cotización ya tiene la OC {$quotation->purchaseOrder->number}.");
        }

        $next = (PurchaseOrder::query()->withTrashed()->where('origin', PurchaseOrderOrigin::Generated)->count()) + 1;

        return PurchaseOrder::query()->create([
            'client_id' => $quotation->client_id,
            'quotation_id' => $quotation->id,
            'origin' => PurchaseOrderOrigin::Generated,
            'number' => sprintf('OC-%05d', $next),
            'date' => now()->toDateString(),
            'amount' => $quotation->total,
            'notes' => "Generada desde la cotización {$quotation->code}",
            'created_by' => $user->id,
        ]);
    }
}
