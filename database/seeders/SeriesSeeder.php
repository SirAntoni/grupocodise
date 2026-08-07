<?php

namespace Database\Seeders;

use App\Models\Series;
use Illuminate\Database\Seeder;

class SeriesSeeder extends Seeder
{
    public function run(): void
    {
        $environment = config('facturacion.environment', 'beta');

        $series = [
            ['document_type' => 'guia_remision', 'code' => 'T001', 'environment' => $environment],
            ['document_type' => 'factura', 'code' => 'F001', 'environment' => $environment],
            // SUNAT exige que la serie de boleta empiece con B.
            ['document_type' => 'boleta', 'code' => 'B001', 'environment' => $environment],
            ['document_type' => 'nota_credito', 'code' => 'FC01', 'environment' => $environment],
        ];

        foreach ($series as $data) {
            Series::query()->firstOrCreate($data);
        }
    }
}
