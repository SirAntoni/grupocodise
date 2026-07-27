<x-page title="Facturas">
    <x-slot name="actions">
        @can('invoices.manage')
            <a href="{{ route('facturas.crear') }}" wire:navigate>
                <x-primary-button>Nueva factura</x-primary-button>
            </a>
        @endcan
    </x-slot>

    <div class="bg-white shadow-sm sm:rounded-lg p-4 grid gap-3 md:grid-cols-5">
        <div>
            <x-input-label value="Número" />
            <x-text-input type="text" class="mt-1 block w-full" placeholder="F001-…"
                          wire:model.live.debounce.300ms="search" />
        </div>
        <div>
            <x-input-label value="Cliente" />
            <select wire:model.live="clientFilter"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">Todos</option>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->business_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label value="Estado" />
            <select wire:model.live="statusFilter"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
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

    <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3">Número</th>
                    <th class="px-4 py-3">Cliente</th>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Vence</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3">SUNAT</th>
                    <th class="px-4 py-3 text-right">Saldo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($invoices as $invoice)
                    <tr wire:key="invoice-{{ $invoice->id }}">
                        <td class="px-4 py-3 font-mono">
                            <a href="{{ route('facturas.ver', $invoice) }}" wire:navigate class="text-indigo-600 hover:underline">
                                {{ $invoice->full_number ?? 'Borrador #'.$invoice->id }}
                            </a>
                        </td>
                        <td class="px-4 py-3">{{ $invoice->client->business_name }}</td>
                        <td class="px-4 py-3">{{ $invoice->issue_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">S/ {{ number_format((float) $invoice->total, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs {{ $invoice->status->badgeColor() }}">
                                {{ $invoice->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if ($invoice->electronicDocument)
                                <span class="px-2 py-0.5 rounded-full text-xs {{ $invoice->electronicDocument->sunat_status->badgeColor() }}">
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
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">Sin facturas que mostrar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $invoices->links() }}</div>
</x-page>
