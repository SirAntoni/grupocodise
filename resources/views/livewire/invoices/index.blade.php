<x-page title="Facturas">
    <x-slot name="actions">
        @can('invoices.manage')
            <a href="{{ route('facturas.crear') }}" wire:navigate>
                <x-primary-button>Nueva factura</x-primary-button>
            </a>
        @endcan
    </x-slot>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm grid gap-3 sm:grid-cols-2 md:grid-cols-5">
        <div>
            <x-input-label value="Número" />
            <x-text-input type="text" class="mt-1 block w-full" placeholder="F001-…"
                          wire:model.live.debounce.300ms="search" />
        </div>
        <div>
            <x-input-label value="Cliente" />
            <select wire:model.live="clientFilter"
                    class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm">
                <option value="">Todos</option>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->business_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label value="Estado" />
            <select wire:model.live="statusFilter"
                    class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm">
                <option value="">Todos</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label value="Emitida desde" />
            <x-text-input type="date" class="mt-1 block w-full" wire:model.live="from" />
        </div>
        <div>
            <x-input-label value="Emitida hasta" />
            <x-text-input type="date" class="mt-1 block w-full" wire:model.live="until" />
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Número</th>
                    <th class="px-4 py-3">Cliente</th>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Vence</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3">SUNAT</th>
                    <th class="px-4 py-3 text-right">Saldo</th>
                    <th class="px-4 py-3 text-right">PDF</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($invoices as $invoice)
                    <tr wire:key="invoice-{{ $invoice->id }}">
                        <td class="px-4 py-3 font-mono">
                            <a href="{{ route('facturas.ver', $invoice) }}" wire:navigate class="font-medium text-brand-600 hover:text-brand-700 hover:underline">
                                {{ $invoice->full_number ?? 'Borrador #'.$invoice->id }}
                            </a>
                        </td>
                        <td class="px-4 py-3">{{ $invoice->client->business_name }}</td>
                        <td class="px-4 py-3">{{ $invoice->issue_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">S/ {{ number_format((float) $invoice->total, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $invoice->status->badgeColor() }}">
                                {{ $invoice->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if ($invoice->electronicDocument)
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $invoice->electronicDocument->sunat_status->badgeColor() }}">
                                    {{ $invoice->electronicDocument->sunat_status->label() }}
                                </span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($invoice->receivable)
                                S/ {{ number_format((float) $invoice->receivable->balance, 2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if ($invoice->full_number)
                                <a href="{{ route('facturas.pdf', $invoice) }}" target="_blank"
                                   class="font-medium text-brand-600 hover:underline">Ver</a>
                                <a href="{{ route('facturas.pdf', [$invoice, 'descargar' => 1]) }}"
                                   class="ms-2 font-medium text-slate-500 hover:underline">Descargar</a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-12 text-center text-sm text-slate-400">Sin facturas que mostrar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $invoices->links() }}</div>
</x-page>
