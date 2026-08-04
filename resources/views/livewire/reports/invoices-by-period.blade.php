@php
    $exportParams = [
        'clientId' => $clientId,
        'periodType' => $periodType,
        'year' => $year,
        'month' => $month,
        'fortnight' => $fortnight,
        'week' => $week,
        'from' => $from,
        'until' => $until,
        'includeAnnulled' => $includeAnnulled ? 1 : 0,
    ];
@endphp

<x-page title="Reporte de facturas">
    <x-slot name="actions">
        <a href="{{ route('reportes.facturas.excel', $exportParams) }}">
            <x-primary-button>Exportar a Excel</x-primary-button>
        </a>
    </x-slot>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200 p-4 grid gap-3 md:grid-cols-3 lg:grid-cols-5 items-end">
        <div>
            <x-input-label value="Empresa" />
            <select wire:model.live="clientId"
                    class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm">
                <option value="">Todas</option>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->business_name }}</option>
                @endforeach
            </select>
        </div>
        @include('livewire.reports.partials.filtros-periodo')

        <label class="inline-flex items-center gap-2 text-sm pb-2">
            <input type="checkbox" wire:model.live="includeAnnulled"
                   class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
            Mostrar anuladas
        </label>
    </div>

    <div class="text-sm text-slate-500">
        Periodo: <strong>{{ $start->format('d/m/Y') }} — {{ $end->format('d/m/Y') }}</strong>
        · {{ $invoices->count() }} factura(s)
        <span class="block text-xs text-slate-400">Los borradores no aparecen: todavía no tienen fecha de emisión.</span>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['Op. gravadas', $totals['gravado'], 'text-slate-900'],
            ['IGV', $totals['igv'], 'text-slate-900'],
            ['Total facturado', $totals['total'], 'text-brand-700'],
            ['Saldo por cobrar', $totals['saldo'], $totals['saldo'] > 0 ? 'text-amber-700' : 'text-emerald-700'],
        ] as [$rotulo, $monto, $color])
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $rotulo }}</p>
                <p class="mt-1 text-xl font-bold tabular-nums {{ $color }}">S/ {{ number_format($monto, 2) }}</p>
            </div>
        @endforeach
    </div>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200 overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-medium uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Factura</th>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Vence</th>
                    <th class="px-4 py-3">Cliente</th>
                    <th class="px-4 py-3">Vendedor</th>
                    <th class="px-4 py-3">Guías</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3 text-right">Saldo</th>
                    <th class="px-4 py-3">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($invoices as $invoice)
                    <tr wire:key="rep-fac-{{ $invoice->id }}" class="{{ $invoice->status === \App\Enums\InvoiceStatus::Annulled ? 'bg-red-50/50 text-slate-400' : '' }}">
                        <td class="px-4 py-2 font-mono">
                            <a href="{{ route('facturas.ver', $invoice) }}" wire:navigate class="text-brand-600 hover:underline">
                                {{ $invoice->full_number }}
                            </a>
                        </td>
                        <td class="px-4 py-2">{{ $invoice->issue_date?->format('d/m/Y') }}</td>
                        <td class="px-4 py-2">{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $invoice->client->business_name }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $invoice->seller?->name ?? '—' }}</td>
                        <td class="px-4 py-2 font-mono text-xs text-slate-500">{{ $invoice->dispatchGuides->pluck('full_number')->implode(', ') }}</td>
                        <td class="px-4 py-2 text-right tabular-nums">{{ number_format((float) $invoice->total, 2) }}</td>
                        <td class="px-4 py-2 text-right tabular-nums">{{ number_format((float) ($invoice->receivable?->balance ?? 0), 2) }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 rounded-full text-xs {{ $invoice->status->badgeColor() }}">
                                {{ $invoice->status->label() }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-slate-400">Sin facturas emitidas en el periodo seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-page>
