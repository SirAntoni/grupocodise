<?php

namespace App\Console\Commands;

use App\Services\ReceivableService;
use Illuminate\Console\Command;

class RefreshTrafficLights extends Command
{
    protected $signature = 'cobranzas:actualizar-semaforo';

    protected $description = 'Recalcula el semáforo (verde/amarillo/rojo) de todas las cuentas por cobrar abiertas';

    public function handle(ReceivableService $receivables): int
    {
        $updated = $receivables->refreshTrafficLights();

        $this->info("Semáforo actualizado: {$updated} cuentas cambiaron de color.");

        return self::SUCCESS;
    }
}
