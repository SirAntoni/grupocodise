<?php

namespace App\Http\Controllers\Reports;

use App\Exports\GuidesExport;
use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class GuidesExportController extends Controller
{
    public function __invoke(Request $request, ReportService $reports)
    {
        // Sin periodo explícito se asume quincenal, así los enlaces antiguos
        // (año + mes + quincena) siguen funcionando igual.
        $period = $request->input('periodType', 'quincenal');

        $data = $request->validate([
            'clientId' => ['nullable', 'integer', 'exists:clients,id'],
            'periodType' => ['nullable', Rule::in(array_keys(ReportService::PERIODS))],
            'year' => ['required_unless:periodType,libre', 'nullable', 'integer', 'between:2020,2100'],
            'month' => ['required_if:periodType,quincenal', 'required_if:periodType,mensual', 'nullable', 'integer', 'between:1,12'],
            'fortnight' => ['required_if:periodType,quincenal', 'nullable', 'integer', 'in:1,2'],
            'week' => ['required_if:periodType,semanal', 'nullable', 'integer', 'between:1,53'],
            'from' => ['required_if:periodType,libre', 'nullable', 'date'],
            'until' => ['required_if:periodType,libre', 'nullable', 'date'],
            'includeAnnulled' => ['nullable', 'boolean'],
        ]);

        [$start, $end] = $reports->resolveRange($period, $data);

        $guides = $reports->guidesInRange(
            $data['clientId'] ?? null,
            $start,
            $end,
            (bool) ($data['includeAnnulled'] ?? false),
        );

        return Excel::download(new GuidesExport($guides), $reports->fileSlug($period, $start, $end));
    }
}
