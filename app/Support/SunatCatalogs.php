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

    /**
     * Catálogo 54 — bienes y servicios sujetos al SPOT (detracción), con su
     * porcentaje vigente. Solo los que aplican al giro; si mañana entra otro,
     * se agrega aquí y aparece solo en el formulario.
     *
     * @var array<string, array{nombre: string, porcentaje: float}>
     */
    public const DETRACTION_GOODS = [
        '037' => ['nombre' => 'Demás servicios gravados con el IGV', 'porcentaje' => 12.0],
        '009' => ['nombre' => 'Arena y piedra', 'porcentaje' => 10.0],
        '013' => ['nombre' => 'Madera', 'porcentaje' => 10.0],
        '020' => ['nombre' => 'Mantenimiento y reparación de bienes muebles', 'porcentaje' => 12.0],
        '022' => ['nombre' => 'Otros servicios empresariales', 'porcentaje' => 12.0],
        '027' => ['nombre' => 'Servicio de transporte de carga', 'porcentaje' => 4.0],
        '030' => ['nombre' => 'Contratos de construcción', 'porcentaje' => 4.0],
    ];

    public static function unitLabel(string $code): string
    {
        return self::UNITS[$code] ?? $code;
    }

    public static function detractionLabel(?string $code): string
    {
        $bien = self::DETRACTION_GOODS[$code] ?? null;

        return $bien ? $code.' — '.$bien['nombre'] : (string) $code;
    }

    public static function detractionPercent(?string $code): ?float
    {
        return self::DETRACTION_GOODS[$code]['porcentaje'] ?? null;
    }
}
