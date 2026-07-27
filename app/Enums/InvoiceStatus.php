<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'borrador';
    case PendingSubmission = 'pendiente_envio';
    case Accepted = 'aceptada';
    case Rejected = 'rechazada';
    case Annulled = 'anulada';

    /**
     * Estados en los que la factura "ocupa" sus guías (no pueden refacturarse).
     * Una rechazada sigue ocupándolas: está en corrección para reenvío; solo
     * la anulación por nota de crédito las libera.
     */
    public static function activeStates(): array
    {
        return [self::Draft, self::PendingSubmission, self::Accepted, self::Rejected];
    }

    public function isActive(): bool
    {
        return in_array($this, self::activeStates(), true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::PendingSubmission => 'Pendiente de envío',
            self::Accepted => 'Aceptada',
            self::Rejected => 'Rechazada',
            self::Annulled => 'Anulada',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Draft => 'bg-gray-100 text-gray-700',
            self::PendingSubmission => 'bg-amber-100 text-amber-800',
            self::Accepted => 'bg-green-100 text-green-800',
            self::Rejected => 'bg-red-100 text-red-800',
            self::Annulled => 'bg-red-100 text-red-800',
        };
    }
}
