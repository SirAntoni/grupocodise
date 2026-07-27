<?php

use Illuminate\Support\Facades\Schedule;

// Recalcula el semáforo de cobranza todos los días temprano.
Schedule::command('cobranzas:actualizar-semaforo')->dailyAt('00:30');

// Procesa la cola de emisión de comprobantes en segundo plano; si el worker
// dedicado (queue:work) no está corriendo, este respaldo drena la cola cada minuto.
Schedule::command('queue:work --stop-when-empty --tries=5')
    ->everyMinute()
    ->withoutOverlapping();
