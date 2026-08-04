<?php

namespace App\Livewire\Reports;

use App\Livewire\Reports\Concerns\HasReportPeriod;
use App\Models\Client;
use App\Services\ReportService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class InvoicesByPeriod extends Component
{
    use HasReportPeriod;

    #[Url]
    public ?int $clientId = null;

    #[Url]
    public bool $includeAnnulled = false;

    public function render(ReportService $reports): View
    {
        [$start, $end] = $this->periodRange($reports);

        $invoices = $reports->invoicesInRange($this->clientId, $start, $end, $this->includeAnnulled);

        return view('livewire.reports.invoices-by-period', [
            'invoices' => $invoices,
            'totals' => $reports->invoiceTotals($invoices),
            'start' => $start,
            'end' => $end,
            'periods' => ReportService::PERIODS,
            'weeks' => $this->periodType === 'semanal' ? $this->weekOptions() : [],
            'clients' => Client::query()->orderBy('business_name')->get(['id', 'business_name']),
        ]);
    }
}
