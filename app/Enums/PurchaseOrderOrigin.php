<?php

namespace App\Enums;

enum PurchaseOrderOrigin: string
{
    case Generated = 'generada';
    case Received = 'recibida';

    public function label(): string
    {
        return match ($this) {
            self::Generated => 'Generada desde cotización',
            self::Received => 'Recibida del cliente',
        };
    }
}
