<?php

namespace App\Http\Controllers\Reports;

use App\Exports\InvoicesExport;
use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class InvoicesExportController extends Controller
{
    public function __invoke(Request $request, ReportService $reports)
    {
        $period = $request->input('periodType', 'quincenal');

        $data = $request->validate([
            ...ReportService::periodRules(),
            'clientId' => ['nullable', 'integer', 'exists:clients,id'],
            'includeAnnulled' => ['nullable', 'boolean'],
        ]);

        [$start, $end] = $reports->resolveRange($period, $data);

        $invoices = $reports->invoicesInRange(
            $data['clientId'] ?? null,
            $start,
            $end,
            (bool) ($data['includeAnnulled'] ?? false),
        );

        return Excel::download(new InvoicesExport($invoices), $reports->fileSlug($period, $start, $end, 'facturas'));
    }
}
