<?php

namespace App\Livewire\Reports\Concerns;

use App\Services\ReportService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;

/**
 * Selector de periodo compartido por los reportes: semana, quincena, mes o
 * rango libre, con los mismos valores por defecto en todos.
 */
trait HasReportPeriod
{
    #[Url]
    public string $periodType = 'quincenal';

    #[Url]
    public ?int $year = null;

    #[Url]
    public ?int $month = null;

    #[Url]
    public ?int $fortnight = null;

    #[Url]
    public ?int $week = null;

    #[Url]
    public ?string $from = null;

    #[Url]
    public ?string $until = null;

    /** Livewire llama solo a este hook por el nombre del trait. */
    public function mountHasReportPeriod(): void
    {
        $this->year ??= now()->year;
        $this->month ??= now()->month;
        $this->fortnight ??= (now()->day <= 15 ? 1 : 2);
        $this->week ??= now()->isoWeek();
        $this->from ??= now()->startOfMonth()->toDateString();
        $this->until ??= now()->toDateString();

        if (! array_key_exists($this->periodType, ReportService::PERIODS)) {
            $this->periodType = 'quincenal';
        }
    }

    /** @return array<string, int|string|null> */
    public function periodParams(): array
    {
        return [
            'year' => $this->year,
            'month' => $this->month,
            'fortnight' => $this->fortnight,
            'week' => $this->week,
            'from' => $this->from,
            'until' => $this->until,
        ];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public function periodRange(ReportService $reports): array
    {
        return $reports->resolveRange($this->periodType, $this->periodParams());
    }

    /**
     * Semanas del año elegido, rotuladas con sus fechas ("32 · 03/08 — 09/08"),
     * que es como las pide el equipo, no por número suelto.
     *
     * @return array<int, string>
     */
    public function weekOptions(): array
    {
        $year = $this->year ?? now()->year;
        $options = [];

        for ($week = 1; $week <= 53; $week++) {
            $start = Carbon::now()->setISODate($year, $week)->startOfWeek(Carbon::MONDAY);

            // El año ISO tiene 52 o 53 semanas: la 53 a veces cae en el siguiente.
            if ($start->isoWeekYear !== $year) {
                continue;
            }

            $options[$week] = $week.' · '.$start->format('d/m').' — '.$start->copy()->endOfWeek(Carbon::SUNDAY)->format('d/m');
        }

        return $options;
    }
}
