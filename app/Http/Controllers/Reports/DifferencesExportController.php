<?php

namespace App\Http\Controllers\Reports;

use App\Exports\DifferencesExport;
use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DifferencesExportController extends Controller
{
    public function __invoke(Request $request, ReportService $reports)
    {
        $data = $request->validate([
            'clientId' => ['nullable', 'integer', 'exists:clients,id'],
            'from' => ['required', 'date'],
            'until' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $rows = $reports->differencesByProduct($data['clientId'] ?? null, $data['from'], $data['until']);

        return Excel::download(
            new DifferencesExport($rows),
            "diferencias-{$data['from']}-al-{$data['until']}.xlsx",
        );
    }
}
