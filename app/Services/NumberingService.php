<?php

namespace App\Services;

use App\Enums\SeriesDocumentType;
use App\Models\Series;
use Illuminate\Support\Facades\DB;

class NumberingService
{
    /**
     * Reserva el siguiente correlativo de la serie con bloqueo pesimista.
     * Debe llamarse dentro de la transacción que persiste el comprobante:
     * si esta falla, el correlativo se revierte y no quedan huecos.
     *
     * @return array{series: Series, number: int, full_number: string}
     */
    public function reserve(SeriesDocumentType $type, ?int $seriesId = null): array
    {
        return DB::transaction(function () use ($type, $seriesId) {
            $series = Series::query()
                ->forType($type)
                ->active()
                // Solo series del ambiente activo: beta y producción numeran aparte.
                ->where('environment', config('facturacion.environment'))
                ->when($seriesId, fn ($q) => $q->whereKey($seriesId))
                ->orderBy('id')
                ->lockForUpdate()
                ->firstOrFail();

            $number = $series->next_number;
            $series->update(['next_number' => $number + 1]);

            return [
                'series' => $series,
                'number' => $number,
                'full_number' => $series->formatNumber($number),
            ];
        });
    }
}
