<x-page title="Cotizaciones">
    <x-slot name="actions">
        @can('quotations.manage')
            <a href="{{ route('cotizaciones.crear') }}" wire:navigate>
                <x-primary-button>Nueva cotización</x-primary-button>
            </a>
        @endcan
    </x-slot>

    <div class="bg-white shadow-sm sm:rounded-lg p-4 grid gap-3 md:grid-cols-3">
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
    </div>

    <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">
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
            <tbody class="divide-y divide-gray-100">
                @forelse ($quotations as $quotation)
                    <tr wire:key="quot-{{ $quotation->id }}">
                        <td class="px-4 py-3 font-mono">
                            <a href="{{ route('cotizaciones.ver', $quotation) }}" wire:navigate class="text-indigo-600 hover:underline">
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
                            <span class="px-2 py-0.5 rounded-full text-xs {{ $quotation->status->badgeColor() }}">
                                {{ $quotation->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-mono">{{ $quotation->purchaseOrder?->number ?? '—' }}</td>
                        <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                            @can('quotations.manage')
                                @if ($quotation->isEditable())
                                    <a href="{{ route('cotizaciones.editar', $quotation) }}" wire:navigate class="text-indigo-600 hover:underline">Editar</a>
                                    <button wire:click="accept({{ $quotation->id }})" class="text-green-700 hover:underline">Aceptar</button>
                                    <button wire:click="reject({{ $quotation->id }})"
                                            wire:confirm="¿Marcar la cotización como rechazada?"
                                            class="text-red-600 hover:underline">Rechazar</button>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">Sin cotizaciones que mostrar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $quotations->links() }}</div>
</x-page>
