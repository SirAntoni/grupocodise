<?php

namespace App\Enums;

enum QuotationStatus: string
{
    case Sent = 'enviada';
    case Accepted = 'aceptada';
    case Rejected = 'rechazada';

    public function label(): string
    {
        return match ($this) {
            self::Sent => 'Enviada',
            self::Accepted => 'Aceptada',
            self::Rejected => 'Rechazada',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Sent => 'bg-blue-100 text-blue-800',
            self::Accepted => 'bg-green-100 text-green-800',
            self::Rejected => 'bg-red-100 text-red-800',
        };
    }
}
