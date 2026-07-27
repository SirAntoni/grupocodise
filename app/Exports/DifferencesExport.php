<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DifferencesExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function __construct(protected Collection $rows) {}

    public function headings(): array
    {
        return ['Código', 'Producto', 'Unidad', 'Solicitado', 'Despachado', 'Diferencia'];
    }

    public function collection(): Collection
    {
        return $this->rows->map(fn ($row) => [
            $row->product->code,
            $row->product->name,
            $row->product->unit_code,
            $row->requested,
            $row->dispatched,
            $row->difference,
        ]);
    }
}
