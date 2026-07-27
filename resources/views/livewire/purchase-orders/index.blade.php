<x-page title="Órdenes de compra">
    <x-slot name="actions">
        @can('purchase-orders.manage')
            <x-primary-button wire:click="openForm">Registrar OC recibida</x-primary-button>
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
            <x-input-label value="Origen" />
            <select wire:model.live="originFilter"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">Todos</option>
                <option value="generada">Generada desde cotización</option>
                <option value="recibida">Recibida del cliente</option>
            </select>
        </div>
    </div>

    <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3">Número</th>
                    <th class="px-4 py-3">Cliente</th>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3 text-right">Monto</th>
                    <th class="px-4 py-3">Origen</th>
                    <th class="px-4 py-3">Cotización</th>
                    <th class="px-4 py-3 text-center">Guías / Facturas</th>
                    <th class="px-4 py-3 text-right">Adjunto</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($orders as $order)
                    <tr wire:key="po-{{ $order->id }}">
                        <td class="px-4 py-3 font-mono">{{ $order->number }}</td>
                        <td class="px-4 py-3">{{ $order->client->business_name }}</td>
                        <td class="px-4 py-3">{{ $order->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-right">S/ {{ number_format((float) $order->amount, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs {{ $order->origin === \App\Enums\PurchaseOrderOrigin::Generated ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                {{ $order->origin->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-mono">
                            @if ($order->quotation)
                                <a href="{{ route('cotizaciones.ver', $order->quotation) }}" wire:navigate class="text-indigo-600 hover:underline">
                                    {{ $order->quotation->code }}
                                </a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">{{ $order->dispatch_guides_count }} / {{ $order->invoices_count }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($order->pdf_path)
                                <a href="{{ route('ordenes-compra.pdf', $order) }}" class="text-indigo-600 hover:underline">Descargar PDF</a>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">Sin órdenes de compra que mostrar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $orders->links() }}</div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-gray-900/50" wire:click="$set('showForm', false)"></div>
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold text-gray-800">Registrar OC recibida del cliente</h3>

                <div>
                    <x-input-label value="Cliente" />
                    <select wire:model="client_id"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        <option value="">Seleccione…</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->business_name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('client_id')" class="mt-1" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label value="Número de OC" />
                        <x-text-input type="text" class="mt-1 block w-full font-mono" wire:model="number" />
                        <x-input-error :messages="$errors->get('number')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label value="Fecha" />
                        <x-text-input type="date" class="mt-1 block w-full" wire:model="date" />
                        <x-input-error :messages="$errors->get('date')" class="mt-1" />
                    </div>
                </div>
                <div>
                    <x-input-label value="Monto (S/)" />
                    <x-text-input type="number" step="0.01" min="0" class="mt-1 block w-full" wire:model="amount" />
                    <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="PDF adjunto (opcional, máx. 5 MB)" />
                    <input type="file" accept="application/pdf" wire:model="pdf"
                           class="mt-1 block w-full text-sm text-gray-600 file:me-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-indigo-700">
                    <div wire:loading wire:target="pdf" class="text-xs text-gray-500 mt-1">Subiendo…</div>
                    <x-input-error :messages="$errors->get('pdf')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Notas" />
                    <x-text-input type="text" class="mt-1 block w-full" wire:model="notes" />
                    <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <x-secondary-button wire:click="$set('showForm', false)">Cancelar</x-secondary-button>
                    <x-primary-button wire:click="save">Registrar</x-primary-button>
                </div>
            </div>
        </div>
    @endif
</x-page>
