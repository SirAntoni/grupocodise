<?php

namespace App\Livewire\Reports;

use App\Models\Client;
use App\Services\ReportService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class BiweeklyGuides extends Component
{
    #[Url]
    public ?int $clientId = null;

    #[Url]
    public ?int $year = null;

    #[Url]
    public ?int $month = null;

    #[Url]
    public ?int $fortnight = null;

    #[Url]
    public bool $includeAnnulled = false;

    public function mount(): void
    {
        $this->year ??= now()->year;
        $this->month ??= now()->month;
        $this->fortnight ??= (now()->day <= 15 ? 1 : 2);
    }

    public function render(ReportService $reports): View
    {
        $guides = $reports->biweeklyGuides(
            $this->clientId,
            $this->year,
            $this->month,
            $this->fortnight,
            $this->includeAnnulled,
        );

        [$start, $end] = $reports->biweeklyRange($this->year, $this->month, $this->fortnight);

        return view('livewire.reports.biweekly-guides', [
            'guides' => $guides,
            'start' => $start,
            'end' => $end,
            'clients' => Client::query()->orderBy('business_name')->get(['id', 'business_name']),
        ]);
    }
}
