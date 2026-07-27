<?php

namespace App\Support;

class NumeroALetras
{
    private const UNIDADES = [
        '', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
        'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE',
        'DIECIOCHO', 'DIECINUEVE', 'VEINTE',
    ];

    private const DECENAS = [
        '', '', 'VEINTI', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA',
    ];

    private const CENTENAS = [
        '', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS',
        'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS',
    ];

    /**
     * Leyenda SUNAT (código 1000): "SON MIL DOSCIENTOS CON 50/100 SOLES".
     */
    public static function legend(float $amount, string $currency = 'SOLES'): string
    {
        $integer = (int) floor($amount);
        $cents = (int) round(($amount - $integer) * 100);

        if ($cents === 100) {
            $integer++;
            $cents = 0;
        }

        return sprintf('SON %s CON %02d/100 %s', self::toWords($integer), $cents, $currency);
    }

    public static function toWords(int $number): string
    {
        if ($number === 0) {
            return 'CERO';
        }

        if ($number < 0) {
            return 'MENOS '.self::toWords(-$number);
        }

        $words = [];

        if ($number >= 1_000_000) {
            $millions = intdiv($number, 1_000_000);
            $words[] = $millions === 1 ? 'UN MILLON' : self::toWords($millions).' MILLONES';
            $number %= 1_000_000;
        }

        if ($number >= 1000) {
            $thousands = intdiv($number, 1000);
            $words[] = $thousands === 1 ? 'MIL' : self::hundreds($thousands).' MIL';
            $number %= 1000;
        }

        if ($number > 0) {
            $words[] = self::hundreds($number);
        }

        return trim(implode(' ', $words));
    }

    private static function hundreds(int $number): string
    {
        if ($number === 100) {
            return 'CIEN';
        }

        $words = [];

        if ($number >= 100) {
            $words[] = self::CENTENAS[intdiv($number, 100)];
            $number %= 100;
        }

        if ($number > 0) {
            $words[] = self::tens($number);
        }

        return implode(' ', $words);
    }

    private static function tens(int $number): string
    {
        if ($number <= 20) {
            return self::UNIDADES[$number];
        }

        $ten = intdiv($number, 10);
        $unit = $number % 10;

        if ($ten === 2) {
            return $unit === 0 ? 'VEINTE' : 'VEINTI'.self::UNIDADES[$unit];
        }

        return self::DECENAS[$ten].($unit > 0 ? ' Y '.self::UNIDADES[$unit] : '');
    }
}
