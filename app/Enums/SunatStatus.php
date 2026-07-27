<?php

namespace App\Enums;

enum SunatStatus: string
{
    case Pending = 'pendiente';
    case Sent = 'enviado';
    case Accepted = 'aceptado';
    case AcceptedWithObservations = 'aceptado_con_observaciones';
    case Rejected = 'rechazado';
    case Error = 'error';

    public function isAccepted(): bool
    {
        return in_array($this, [self::Accepted, self::AcceptedWithObservations], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente de envío',
            self::Sent => 'Enviado',
            self::Accepted => 'Aceptado',
            self::AcceptedWithObservations => 'Aceptado con observaciones',
            self::Rejected => 'Rechazado',
            self::Error => 'Error de envío',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Pending => 'bg-gray-100 text-gray-700',
            self::Sent => 'bg-blue-100 text-blue-800',
            self::Accepted => 'bg-green-100 text-green-800',
            self::AcceptedWithObservations => 'bg-lime-100 text-lime-800',
            self::Rejected => 'bg-red-100 text-red-800',
            self::Error => 'bg-orange-100 text-orange-800',
        };
    }
}
