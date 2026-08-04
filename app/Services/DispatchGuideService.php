<?php

namespace App\Services;

use App\Enums\DispatchGuideStatus;
use App\Enums\RequirementStatus;
use App\Enums\SeriesDocumentType;
use App\Enums\SunatStatus;
use App\Models\DispatchGuide;
use App\Models\Requirement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DispatchGuideService
{
    public function __construct(
        protected StockService $stock,
        protected NumberingService $numbering,
    ) {}

    /**
     * Crea una guía en borrador con los datos del requerimiento precargados.
     */
    public function createFromRequirement(Requirement $requirement, User $user): DispatchGuide
    {
        if ($requirement->status === RequirementStatus::Annulled) {
            throw new InvalidArgumentException('No se puede generar una guía de un requerimiento anulado.');
        }

        return DB::transaction(function () use ($requirement, $user) {
            $guide = DispatchGuide::query()->create([
                'client_id' => $requirement->client_id,
                'requirement_id' => $requirement->id,
                'status' => DispatchGuideStatus::Draft,
                'transfer_date' => $requirement->required_date,
                'origin_address' => config('facturacion.company.address.direccion'),
                'origin_ubigeo' => config('facturacion.company.address.ubigeo'),
                'delivery_address' => $requirement->delivery_address,
                'district' => $requirement->district,
                'crew_chief' => $requirement->crew_chief,
                'created_by' => $user->id,
            ]);

            foreach ($requirement->items as $item) {
                $guide->items()->create([
                    'product_id' => $item->product_id,
                    'description' => $item->product->name,
                    'unit_code' => $item->product->unit_code,
                    'quantity_requested' => $item->quantity,
                    'quantity_dispatched' => $item->quantity,
                ]);
            }

            return $guide;
        });
    }

    /**
     * Crea una copia editable en borrador; recibirá su propia numeración al emitirse.
     */
    public function duplicate(DispatchGuide $guide, User $user): DispatchGuide
    {
        return DB::transaction(function () use ($guide, $user) {
            $copy = $guide->replicate([
                'series_id', 'number', 'full_number', 'issue_date',
                'annulment_reason', 'annulled_at', 'annulled_by', 'issued_by',
            ]);
            $copy->status = DispatchGuideStatus::Draft;
            $copy->created_by = $user->id;
            $copy->save();

            foreach ($guide->items as $item) {
                $copy->items()->create([
                    'product_id' => $item->product_id,
                    'description' => $item->description,
                    'unit_code' => $item->unit_code,
                    'quantity_requested' => $item->quantity_requested,
                    'quantity_dispatched' => $item->quantity_dispatched,
                ]);
            }

            return $copy;
        });
    }

    /**
     * Emite la guía: asigna serie y correlativo, descuenta stock por la
     * cantidad despachada y deja el documento electrónico pendiente de envío.
     * Todo dentro de una transacción: si el stock no alcanza, no se consume
     * numeración ni cambia el estado.
     */
    public function issue(DispatchGuide $guide, User $user): DispatchGuide
    {
        if (! $guide->isDraft()) {
            throw new InvalidArgumentException('Solo se puede emitir una guía en borrador.');
        }

        if ($guide->items()->count() === 0) {
            throw new InvalidArgumentException('La guía no tiene ítems.');
        }

        if ((float) $guide->items()->sum('quantity_dispatched') <= 0) {
            throw new InvalidArgumentException('La cantidad despachada total debe ser mayor a cero.');
        }

        return DB::transaction(function () use ($guide, $user) {
            // Re-verificar el estado bajo bloqueo: dos emisiones concurrentes
            // de la misma guía no deben descontar stock dos veces.
            $guide = DispatchGuide::query()->whereKey($guide->getKey())->lockForUpdate()->firstOrFail();

            if (! $guide->isDraft()) {
                throw new InvalidArgumentException('La guía ya fue emitida o anulada.');
            }

            $numbering = $this->numbering->reserve(SeriesDocumentType::DispatchGuide, $guide->series_id);

            $guide->update([
                'series_id' => $numbering['series']->id,
                'number' => $numbering['number'],
                'full_number' => $numbering['full_number'],
                'status' => DispatchGuideStatus::Issued,
                'issue_date' => now()->toDateString(),
                'issued_by' => $user->id,
            ]);

            $this->stock->dispatchGuideItems($guide, $user);

            if ($guide->requirement && $guide->requirement->status === RequirementStatus::Pending) {
                $guide->requirement->update(['status' => RequirementStatus::Dispatched]);
            }

            $document = $guide->electronicDocument()->create([
                'environment' => config('facturacion.environment'),
                'sunat_status' => SunatStatus::Pending,
            ]);

            \App\Jobs\SubmitElectronicDocument::dispatch($document)->afterCommit();

            return $guide->refresh();
        });
    }

    /**
     * Reenvía a SUNAT una guía cuyo envío fue rechazado o falló, con la misma
     * numeración (idempotente). Si el documento ya tiene ticket, el job
     * reconsulta el estado en lugar de reenviar.
     */
    public function resend(DispatchGuide $guide, User $user): void
    {
        $document = $guide->electronicDocument;

        if (! $document || $document->sunat_status->isAccepted()) {
            throw new InvalidArgumentException('La guía no está pendiente de corrección.');
        }

        $document->update([
            'sunat_status' => \App\Enums\SunatStatus::Pending,
            // Un rechazo invalida el ticket anterior: se reenvía desde cero.
            'ticket' => $document->sunat_status === \App\Enums\SunatStatus::Rejected ? null : $document->ticket,
            // El contador cuenta los intentos de este reenvío, no los históricos.
            'attempts' => 0,
        ]);
        $document->logs()->create([
            'action' => 'enviar',
            'success' => true,
            'response_message' => 'Reenvío manual solicitado',
            'user_id' => $user->id,
        ]);

        \App\Jobs\SubmitElectronicDocument::dispatch($document);
    }

    /**
     * Anula una guía emitida con motivo obligatorio y restituye el stock.
     */
    public function annul(DispatchGuide $guide, string $reason, User $user): DispatchGuide
    {
        if (! $guide->isIssued()) {
            throw new InvalidArgumentException('Solo se puede anular una guía emitida.');
        }

        if ($activeInvoice = $guide->activeInvoice()) {
            throw new InvalidArgumentException(
                "La guía está asociada a la factura {$activeInvoice->full_number}; anule primero la factura.",
            );
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('El motivo de anulación es obligatorio.');
        }

        return DB::transaction(function () use ($guide, $reason, $user) {
            // Re-verificar bajo bloqueo: una doble anulación restituiría el
            // stock dos veces.
            $guide = DispatchGuide::query()->whereKey($guide->getKey())->lockForUpdate()->firstOrFail();

            if (! $guide->isIssued()) {
                throw new InvalidArgumentException('La guía ya no está emitida.');
            }

            if ($guide->activeInvoice()) {
                throw new InvalidArgumentException('La guía está asociada a una factura activa; anule primero la factura.');
            }

            $this->stock->restituteGuideItems($guide, $user);

            $guide->update([
                'status' => DispatchGuideStatus::Annulled,
                'annulment_reason' => $reason,
                'annulled_at' => now(),
                'annulled_by' => $user->id,
            ]);

            return $guide->refresh();
        });
    }
}
