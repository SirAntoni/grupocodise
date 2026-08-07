<?php

namespace App\Enums;

enum SeriesDocumentType: string
{
    case DispatchGuide = 'guia_remision';
    case Invoice = 'factura';
    case Receipt = 'boleta';
    case CreditNote = 'nota_credito';

    /** Código de tipo de documento SUNAT (catálogo 01 / GRE). */
    public function sunatCode(): string
    {
        return match ($this) {
            self::DispatchGuide => '09',
            self::Invoice => '01',
            self::Receipt => '03',
            self::CreditNote => '07',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::DispatchGuide => 'Guía de remisión',
            self::Invoice => 'Factura',
            self::Receipt => 'Boleta de venta',
            self::CreditNote => 'Nota de crédito',
        };
    }

    /** Letra con la que SUNAT exige que empiece la serie. */
    public function seriesPrefix(): string
    {
        return match ($this) {
            self::DispatchGuide => 'T',
            self::Invoice => 'F',
            self::Receipt => 'B',
            self::CreditNote => 'F',
        };
    }
}
