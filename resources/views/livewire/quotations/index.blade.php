<x-page title="Cotizaciones">
    <x-slot name="actions">
        @can('quotations.manage')
            <a href="{{ route('cotizaciones.crear') }}" wire:navigate>
                <x-primary-button>Nueva cotización</x-primary-button>
            </a>
        @endcan
    </x-slot>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200 p-4 grid gap-3 sm:grid-cols-2">
        <div>
            <x-input-label value="Cliente" />
            <select wire:model.live="clientFilter"
                    class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-sm">
                <option value="">Todos</option>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->business_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label value="Estado" />
            <select wire:model.live="statusFilter"
                    class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-sm">
                <option value="">Todos</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Código</th>
                    <th class="px-4 py-3">Cliente</th>
                    <th class="px-4 py-3">Emisión</th>
                    <th class="px-4 py-3">Vigente hasta</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3">OC</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($quotations as $quotation)
                    <tr wire:key="quot-{{ $quotation->id }}" class="transition hover:bg-slate-50/70">
                        <td class="px-4 py-3 font-mono">
                            <a href="{{ route('cotizaciones.ver', $quotation) }}" wire:navigate class="font-medium text-brand-700 hover:text-brand-800 hover:underline">
                                {{ $quotation->code }}
                            </a>
                        </td>
                        <td class="px-4 py-3">{{ $quotation->client->business_name }}</td>
                        <td class="px-4 py-3">{{ $quotation->issue_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 {{ $quotation->valid_until->isPast() ? 'text-red-600' : '' }}">
                            {{ $quotation->valid_until->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3 text-right">S/ {{ number_format((float) $quotation->total, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $quotation->status->badgeColor() }}">
                                {{ $quotation->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-mono">{{ $quotation->purchaseOrder?->number ?? '—' }}</td>
                        <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                            <a href="{{ route('cotizaciones.pdf', $quotation) }}" target="_blank" class="font-medium text-slate-600 hover:underline">PDF</a>
                            @can('quotations.manage')
                                @if ($quotation->isEditable())
                                    <a href="{{ route('cotizaciones.editar', $quotation) }}" wire:navigate class="font-medium text-brand-700 hover:text-brand-800 hover:underline">Editar</a>
                                    <button wire:click="accept({{ $quotation->id }})" class="text-green-700 hover:underline">Aceptar</button>
                                    <button @click="$store.confirm.open('¿Marcar la cotización {{ $quotation->code }} como rechazada?', () => $wire.reject({{ $quotation->id }}), { danger: true, confirmText: 'Sí, rechazar' })"
                                            class="text-red-600 hover:underline">Rechazar</button>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center">
                            <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg>
                            <p class="mt-2 text-sm text-slate-400">Sin cotizaciones que mostrar.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pt-1">{{ $quotations->links() }}</div>
</x-page>
