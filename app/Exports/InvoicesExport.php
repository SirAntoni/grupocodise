<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InvoicesExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function __construct(protected Collection $invoices) {}

    public function headings(): array
    {
        return [
            'Factura', 'Fecha emisión', 'Vencimiento', 'Estado', 'Cliente', 'RUC',
            'Vendedor', 'Orden de compra', 'Guías', 'Forma de pago',
            'Op. gravadas', 'IGV', 'Total', 'Cobrado', 'Saldo', 'Estado SUNAT',
        ];
    }

    public function collection(): Collection
    {
        return $this->invoices->map(fn ($invoice) => [
            $invoice->full_number,
            $invoice->issue_date?->format('d/m/Y'),
            $invoice->due_date?->format('d/m/Y'),
            $invoice->status->label(),
            $invoice->client->business_name,
            $invoice->client->ruc,
            $invoice->seller?->name ?? '',
            $invoice->purchaseOrder?->number ?? '',
            $invoice->dispatchGuides->pluck('full_number')->implode(', '),
            $invoice->payment_type === 'contado' ? 'Contado' : 'Crédito',
            (float) $invoice->taxable_amount,
            (float) $invoice->igv,
            (float) $invoice->total,
            (float) ($invoice->receivable?->paid_amount ?? 0),
            (float) ($invoice->receivable?->balance ?? 0),
            $invoice->electronicDocument?->sunat_status?->label() ?? '',
        ]);
    }
}
