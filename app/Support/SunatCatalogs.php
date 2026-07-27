<?php

namespace App\Support;

class SunatCatalogs
{
    /**
     * Catálogo 03 — unidades de medida más usadas en despacho de materiales.
     *
     * @var array<string, string>
     */
    public const UNITS = [
        'NIU' => 'Unidad',
        'KGM' => 'Kilogramo',
        'MTR' => 'Metro',
        'MTQ' => 'Metro cúbico',
        'MTK' => 'Metro cuadrado',
        'LTR' => 'Litro',
        'GLL' => 'Galón',
        'BG' => 'Bolsa',
        'PR' => 'Par',
        'CEN' => 'Ciento',
        'MIL' => 'Millar',
        'SET' => 'Juego',
    ];

    /**
     * Catálogo 20 — motivos de traslado usados por el negocio.
     *
     * @var array<string, string>
     */
    public const TRANSFER_REASONS = [
        '01' => 'Venta',
        '02' => 'Compra',
        '04' => 'Traslado entre establecimientos',
        '13' => 'Otros',
    ];

    /**
     * Catálogo 09 — tipos de nota de crédito usados por el negocio.
     *
     * @var array<string, string>
     */
    public const CREDIT_NOTE_TYPES = [
        '01' => 'Anulación de la operación',
        '02' => 'Anulación por error en el RUC',
        '06' => 'Devolución total',
    ];

    public static function unitLabel(string $code): string
    {
        return self::UNITS[$code] ?? $code;
    }
}
