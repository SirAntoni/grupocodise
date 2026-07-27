<?php

namespace App\Enums;

enum InvoiceItemType: string
{
    case Product = 'producto';
    case RemoteZone = 'zona_lejana';

    public function label(): string
    {
        return match ($this) {
            self::Product => 'Producto',
            self::RemoteZone => 'Zona lejana',
        };
    }
}
