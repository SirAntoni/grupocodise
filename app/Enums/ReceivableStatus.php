<?php

namespace App\Enums;

enum ReceivableStatus: string
{
    case Pending = 'pendiente';
    case Partial = 'parcial';
    case Paid = 'pagada';
    case Annulled = 'anulada';

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Partial], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Partial => 'Pago parcial',
            self::Paid => 'Pagada',
            self::Annulled => 'Anulada',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-800',
            self::Partial => 'bg-blue-100 text-blue-800',
            self::Paid => 'bg-green-100 text-green-800',
            self::Annulled => 'bg-gray-200 text-gray-600',
        };
    }
}
