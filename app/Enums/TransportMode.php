<?php

namespace App\Enums;

enum TransportMode: string
{
    // Códigos del catálogo 18 de SUNAT (modalidad de traslado).
    case Public = 'publico';
    case Private = 'privado';

    public function sunatCode(): string
    {
        return match ($this) {
            self::Public => '01',
            self::Private => '02',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Transporte público (transportista)',
            self::Private => 'Transporte privado (vehículo propio)',
        };
    }
}
