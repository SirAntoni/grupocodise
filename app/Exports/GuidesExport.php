<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GuidesExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function __construct(protected Collection $guides) {}

    public function headings(): array
    {
        return [
            'Guía', 'Fecha emisión', 'Estado', 'Cliente', 'RUC', 'Requerimiento',
            'Jefe de cuadrilla', 'Distrito', 'Producto', 'Unidad',
            'Cant. solicitada', 'Cant. despachada', 'Diferencia', 'Factura',
        ];
    }

    public function collection(): Collection
    {
        return $this->guides->flatMap(fn ($guide) => $guide->items->map(fn ($item) => [
            $guide->full_number,
            $guide->issue_date?->format('d/m/Y'),
            $guide->status->label(),
            $guide->client->business_name,
            $guide->client->ruc,
            $guide->requirement?->code ?? '',
            $guide->crew_chief,
            $guide->district,
            $item->description,
            $item->unit_code,
            (float) $item->quantity_requested,
            (float) $item->quantity_dispatched,
            round((float) $item->quantity_requested - (float) $item->quantity_dispatched, 2),
            $guide->invoices->pluck('full_number')->implode(', '),
        ]));
    }
}
