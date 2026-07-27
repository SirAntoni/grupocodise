<?php

namespace App\Enums;

enum CreditNoteStatus: string
{
    case PendingSubmission = 'pendiente_envio';
    case Accepted = 'aceptada';
    case Rejected = 'rechazada';

    public function label(): string
    {
        return match ($this) {
            self::PendingSubmission => 'Pendiente de envío',
            self::Accepted => 'Aceptada',
            self::Rejected => 'Rechazada',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::PendingSubmission => 'bg-amber-100 text-amber-800',
            self::Accepted => 'bg-green-100 text-green-800',
            self::Rejected => 'bg-red-100 text-red-800',
        };
    }
}
