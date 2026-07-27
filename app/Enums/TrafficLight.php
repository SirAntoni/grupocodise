<?php

namespace App\Enums;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

enum TrafficLight: string
{
    case Green = 'verde';
    case Yellow = 'amarillo';
    case Red = 'rojo';

    /** Días de anticipación con los que la cobranza pasa a amarillo. */
    public const YELLOW_THRESHOLD_DAYS = 5;

    /**
     * Calcula el color según el vencimiento: rojo = vencida,
     * amarillo = vence en 5 días o menos, verde = en plazo.
     */
    public static function forDueDate(CarbonInterface $dueDate, ?CarbonInterface $today = null): self
    {
        $today = ($today ?? Carbon::today())->startOfDay();
        $dueDate = $dueDate->copy()->startOfDay();

        if ($dueDate->lessThan($today)) {
            return self::Red;
        }

        if ($today->diffInDays($dueDate) <= self::YELLOW_THRESHOLD_DAYS) {
            return self::Yellow;
        }

        return self::Green;
    }

    public function label(): string
    {
        return match ($this) {
            self::Green => 'En plazo',
            self::Yellow => 'Por vencer',
            self::Red => 'Vencida',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Green => 'bg-green-100 text-green-800',
            self::Yellow => 'bg-amber-100 text-amber-800',
            self::Red => 'bg-red-100 text-red-800',
        };
    }
}
