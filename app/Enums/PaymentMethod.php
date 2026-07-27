<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Transfer = 'transferencia';
    case Deposit = 'deposito';
    case Cash = 'efectivo';
    case Check = 'cheque';
    case Other = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Transfer => 'Transferencia',
            self::Deposit => 'Depósito',
            self::Cash => 'Efectivo',
            self::Check => 'Cheque',
            self::Other => 'Otro',
        };
    }
}
