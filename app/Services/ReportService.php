<?php

namespace App\Services;

use App\Enums\DispatchGuideStatus;
use App\Enums\InvoiceStatus;
use App\Models\DispatchGuide;
use App\Models\DispatchGuideItem;
use App\Models\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class ReportService
{
    /** Periodos que ofrece el reporte de guías. */
    public const PERIODS = [
        'semanal' => 'Semanal',
        'quincenal' => 'Quincenal',
        'mensual' => 'Mensual',
        'libre' => 'Rango de fechas',
    ];

    /**
     * Rango de fechas de una quincena: 1 = del 1 al 15, 2 = del 16 a fin de mes.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function biweeklyRange(int $year, int $month, int $fortnight): array
    {
        $start = Carbon::create($year, $month, $fortnight === 1 ? 1 : 16)->startOfDay();
        $end = $fortnight === 1
            ? Carbon::create($year, $month, 15)->endOfDay()
            : Carbon::create($year, $month, 1)->endOfMonth();

        return [$start, $end];
    }

    /**
     * Rango de cualquiera de los periodos. La semana va de lunes a domingo.
     *
     * @param  array{year?: int|null, month?: int|null, fortnight?: int|null, week?: int|null, from?: string|null, until?: string|null}  $params
     * @return array{0: Carbon, 1: Carbon}
     */
    public function resolveRange(string $period, array $params): array
    {
        $year = (int) ($params['year'] ?? now()->year);
        $month = (int) ($params['month'] ?? now()->month);

        if ($period === 'semanal') {
            $start = Carbon::now()->setISODate($year, (int) ($params['week'] ?? now()->isoWeek()))->startOfWeek(Carbon::MONDAY);

            return [$start, $start->copy()->endOfWeek(Carbon::SUNDAY)];
        }

        if ($period === 'mensual') {
            $start = Carbon::create($year, $month, 1)->startOfMonth();

            return [$start, $start->copy()->endOfMonth()];
        }

        if ($period === 'libre') {
            $start = Carbon::parse($params['from'] ?: now()->startOfMonth())->startOfDay();
            $end = Carbon::parse($params['until'] ?: now())->endOfDay();

            // Fechas invertidas: se ordenan en vez de devolver un rango vacío.
            return $start->lte($end) ? [$start, $end] : [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return $this->biweeklyRange($year, $month, (int) ($params['fortnight'] ?? 1));
    }

    /**
     * Nombre de archivo del export, según el periodo consultado.
     */
    public function fileSlug(string $period, Carbon $start, Carbon $end, string $prefix = 'guias'): string
    {
        return sprintf(
            '%s-%s-%s-al-%s.xlsx',
            $prefix,
            array_key_exists($period, self::PERIODS) ? $period : 'periodo',
            $start->format('Y-m-d'),
            $end->format('Y-m-d'),
        );
    }

    /**
     * Reglas de validación del periodo, iguales para todos los exports. Sin
     * `periodType` se asume quincenal, así los enlaces antiguos siguen sirviendo.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function periodRules(): array
    {
        return [
            'periodType' => ['nullable', Rule::in(array_keys(self::PERIODS))],
            'year' => ['required_unless:periodType,libre', 'nullable', 'integer', 'between:2020,2100'],
            'month' => ['required_if:periodType,quincenal', 'required_if:periodType,mensual', 'nullable', 'integer', 'between:1,12'],
            'fortnight' => ['required_if:periodType,quincenal', 'nullable', 'integer', 'in:1,2'],
            'week' => ['required_if:periodType,semanal', 'nullable', 'integer', 'between:1,53'],
            'from' => ['required_if:periodType,libre', 'nullable', 'date'],
            'until' => ['required_if:periodType,libre', 'nullable', 'date'],
        ];
    }

    /**
     * Guías del periodo quincenal con sus ítems, por empresa.
     * Incluye emitidas; las anuladas solo si $includeAnnulled.
     */
    public function biweeklyGuides(?int $clientId, int $year, int $month, int $fortnight, bool $includeAnnulled): Collection
    {
        [$start, $end] = $this->biweeklyRange($year, $month, $fortnight);

        return $this->guidesInRange($clientId, $start, $end, $includeAnnulled);
    }

    /**
     * Guías emitidas entre dos fechas, con sus ítems, por empresa.
     */
    public function guidesInRange(?int $clientId, Carbon $start, Carbon $end, bool $includeAnnulled): Collection
    {
        $statuses = [DispatchGuideStatus::Issued];
        if ($includeAnnulled) {
            $statuses[] = DispatchGuideStatus::Annulled;
        }

        return DispatchGuide::query()
            ->with(['client', 'requirement', 'items.product', 'invoices' => fn ($q) => $q->whereNotNull('full_number')])
            ->whereIn('status', $statuses)
            ->whereBetween('issue_date', [$start->toDateString(), $end->toDateString()])
            ->when($clientId, fn ($q) => $q->where('client_id', $clientId))
            ->orderBy('issue_date')
            ->orderBy('full_number')
            ->get();
    }

    /**
     * Facturas emitidas entre dos fechas. Los borradores no tienen fecha de
     * emisión, así que quedan fuera por definición; las anuladas solo si se
     * piden expresamente.
     */
    public function invoicesInRange(?int $clientId, Carbon $start, Carbon $end, bool $includeAnnulled): Collection
    {
        $statuses = [InvoiceStatus::PendingSubmission, InvoiceStatus::Accepted, InvoiceStatus::Rejected];
        if ($includeAnnulled) {
            $statuses[] = InvoiceStatus::Annulled;
        }

        return Invoice::query()
            ->with(['client', 'seller', 'purchaseOrder', 'receivable', 'dispatchGuides', 'electronicDocument'])
            ->whereIn('status', $statuses)
            ->whereBetween('issue_date', [$start->toDateString(), $end->toDateString()])
            ->when($clientId, fn ($q) => $q->where('client_id', $clientId))
            ->orderBy('issue_date')
            ->orderBy('full_number')
            ->get();
    }

    /**
     * Totales del reporte de facturas: lo primero que mira el que cobra.
     *
     * @return array{gravado: float, igv: float, total: float, cobrado: float, saldo: float}
     */
    public function invoiceTotals(Collection $invoices): array
    {
        return [
            'gravado' => round((float) $invoices->sum('taxable_amount'), 2),
            'igv' => round((float) $invoices->sum('igv'), 2),
            'total' => round((float) $invoices->sum('total'), 2),
            'cobrado' => round((float) $invoices->sum(fn ($i) => (float) ($i->receivable?->paid_amount ?? 0)), 2),
            'saldo' => round((float) $invoices->sum(fn ($i) => (float) ($i->receivable?->balance ?? 0)), 2),
        ];
    }

    /**
     * Diferencias solicitado vs. despachado por producto en un periodo
     * (solo guías emitidas).
     */
    public function differencesByProduct(?int $clientId, string $from, string $until): Collection
    {
        return DispatchGuideItem::query()
            ->selectRaw('product_id, SUM(quantity_requested) as requested, SUM(quantity_dispatched) as dispatched')
            ->whereHas('dispatchGuide', fn ($q) => $q
                ->where('status', DispatchGuideStatus::Issued)
                ->whereBetween('issue_date', [$from, $until])
                ->when($clientId, fn ($qq) => $qq->where('client_id', $clientId)))
            ->groupBy('product_id')
            ->with('product')
            ->get()
            ->map(fn ($row) => (object) [
                'product' => $row->product,
                'requested' => (float) $row->requested,
                'dispatched' => (float) $row->dispatched,
                'difference' => round((float) $row->requested - (float) $row->dispatched, 2),
            ])
            ->sortByDesc(fn ($row) => abs($row->difference))
            ->values();
    }
}
