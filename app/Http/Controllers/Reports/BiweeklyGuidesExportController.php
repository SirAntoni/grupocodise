<?php

namespace App\Http\Controllers\Reports;

use App\Exports\BiweeklyGuidesExport;
use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class BiweeklyGuidesExportController extends Controller
{
    public function __invoke(Request $request, ReportService $reports)
    {
        $data = $request->validate([
            'clientId' => ['nullable', 'integer', 'exists:clients,id'],
            'year' => ['required', 'integer', 'between:2020,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
            'fortnight' => ['required', 'integer', 'in:1,2'],
            'includeAnnulled' => ['nullable', 'boolean'],
        ]);

        $guides = $reports->biweeklyGuides(
            $data['clientId'] ?? null,
            (int) $data['year'],
            (int) $data['month'],
            (int) $data['fortnight'],
            (bool) ($data['includeAnnulled'] ?? false),
        );

        $name = sprintf('guias-quincena-%d-%02d-Q%d.xlsx', $data['year'], $data['month'], $data['fortnight']);

        return Excel::download(new BiweeklyGuidesExport($guides), $name);
    }
}
