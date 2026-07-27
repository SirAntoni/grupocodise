<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Entry = 'entrada';
    case DispatchExit = 'salida_guia';
    case AnnulmentRestitution = 'restitucion_anulacion';
    case Adjustment = 'ajuste';

    /**
     * Sentido del movimiento sobre el stock: +1 suma, -1 resta.
     * Los ajustes llevan el signo en la propia cantidad.
     */
    public function direction(): int
    {
        return match ($this) {
            self::Entry, self::AnnulmentRestitution => 1,
            self::DispatchExit => -1,
            self::Adjustment => 1,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Entry => 'Entrada',
            self::DispatchExit => 'Salida por guía',
            self::AnnulmentRestitution => 'Restitución por anulación',
            self::Adjustment => 'Ajuste',
        };
    }
}
