<?php

namespace App\Livewire\Reports;

use App\Models\Client;
use App\Services\ReportService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class GuidesByPeriod extends Component
{
    #[Url]
    public ?int $clientId = null;

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

    #[Url]
    public bool $includeAnnulled = false;

    public function mount(): void
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

    public function render(ReportService $reports): View
    {
        [$start, $end] = $reports->resolveRange($this->periodType, [
            'year' => $this->year,
            'month' => $this->month,
            'fortnight' => $this->fortnight,
            'week' => $this->week,
            'from' => $this->from,
            'until' => $this->until,
        ]);

        return view('livewire.reports.guides-by-period', [
            'guides' => $reports->guidesInRange($this->clientId, $start, $end, $this->includeAnnulled),
            'start' => $start,
            'end' => $end,
            'periods' => ReportService::PERIODS,
            'weeks' => $this->periodType === 'semanal' ? $this->weekOptions() : [],
            'clients' => Client::query()->orderBy('business_name')->get(['id', 'business_name']),
        ]);
    }
}
