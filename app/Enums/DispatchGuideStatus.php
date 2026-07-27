<?php

namespace App\Enums;

enum DispatchGuideStatus: string
{
    case Draft = 'borrador';
    case Issued = 'emitida';
    case Annulled = 'anulada';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Issued => 'Emitida',
            self::Annulled => 'Anulada',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Draft => 'bg-gray-100 text-gray-700',
            self::Issued => 'bg-green-100 text-green-800',
            self::Annulled => 'bg-red-100 text-red-800',
        };
    }
}
