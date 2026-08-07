<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

/**
 * Cliente genérico para la boleta de mostrador: cuando la venta es por debajo
 * del tope de SUNAT no hace falta identificar al comprador, pero el sistema
 * igual necesita a nombre de quién emitir.
 *
 * Es idempotente y no trae datos de demostración, así que puede correrse en
 * producción sin riesgo.
 */
class DefaultClientsSeeder extends Seeder
{
    public function run(): void
    {
        Client::query()->firstOrCreate(
            ['document_type' => '0', 'document_number' => '00000000'],
            [
                'business_name' => 'CLIENTES VARIOS',
                'address' => '-',
                'is_active' => true,
            ],
        );
    }
}
