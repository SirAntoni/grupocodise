<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Datos maestros de ejemplo para empezar a operar en desarrollo.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $clients = [
            ['business_name' => 'CONSTRUCTORA LOS ANDES S.A.C.', 'document_number' => '20512345678', 'address' => 'Av. Javier Prado Este 4200, Surco', 'ubigeo' => '150140', 'district' => 'Santiago de Surco'],
            ['business_name' => 'INGENIERÍA Y MONTAJES DEL SUR S.A.', 'document_number' => '20487654321', 'address' => 'Calle Los Talleres 180, Ate', 'ubigeo' => '150103', 'district' => 'Ate'],
            ['business_name' => 'SERVICIOS ELÉCTRICOS LIMA NORTE E.I.R.L.', 'document_number' => '20609876543', 'address' => 'Av. Túpac Amaru 1500, Comas', 'ubigeo' => '150110', 'district' => 'Comas'],
        ];

        foreach ($clients as $data) {
            Client::query()->firstOrCreate(['document_number' => $data['document_number']], $data);
        }

        $products = [
            ['code' => 'CEM-001', 'name' => 'Cemento Portland Tipo I x 42.5 kg', 'unit_code' => 'NIU', 'stock' => 500],
            ['code' => 'FIE-012', 'name' => 'Fierro corrugado 1/2" x 9 m', 'unit_code' => 'NIU', 'stock' => 300],
            ['code' => 'ARE-001', 'name' => 'Arena gruesa', 'unit_code' => 'MTQ', 'stock' => 80],
            ['code' => 'LAD-001', 'name' => 'Ladrillo King Kong 18 huecos', 'unit_code' => 'NIU', 'stock' => 5000],
            ['code' => 'CAB-014', 'name' => 'Cable THW 14 AWG (rollo 100 m)', 'unit_code' => 'NIU', 'stock' => 120],
            ['code' => 'TUB-034', 'name' => 'Tubería PVC 3/4" x 5 m', 'unit_code' => 'NIU', 'stock' => 250],
            ['code' => 'PIN-001', 'name' => 'Pintura látex blanco (galón)', 'unit_code' => 'GLL', 'stock' => 60],
        ];

        foreach ($products as $data) {
            Product::query()->firstOrCreate(['code' => $data['code']], $data);
        }
    }
}
