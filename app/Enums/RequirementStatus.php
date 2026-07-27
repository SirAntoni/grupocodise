<?php

namespace App\Enums;

enum RequirementStatus: string
{
    case Pending = 'pendiente';
    case Dispatched = 'despachado';
    case Annulled = 'anulado';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Dispatched => 'Despachado',
            self::Annulled => 'Anulado',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-800',
            self::Dispatched => 'bg-green-100 text-green-800',
            self::Annulled => 'bg-gray-200 text-gray-600',
        };
    }
}
