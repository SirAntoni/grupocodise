<?php

namespace App\Livewire\Reports;

use App\Models\Client;
use App\Services\ReportService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Differences extends Component
{
    #[Url]
    public ?int $clientId = null;

    #[Url]
    public ?string $from = null;

    #[Url]
    public ?string $until = null;

    public function mount(): void
    {
        $this->from ??= now()->startOfMonth()->toDateString();
        $this->until ??= now()->toDateString();
    }

    public function render(ReportService $reports): View
    {
        $rows = $reports->differencesByProduct($this->clientId, $this->from, $this->until);

        return view('livewire.reports.differences', [
            'rows' => $rows,
            'clients' => Client::query()->orderBy('business_name')->get(['id', 'business_name']),
        ]);
    }
}
