<x-page title="Órdenes de compra">
    <x-slot name="actions">
        @can('purchase-orders.manage')
            <x-primary-button wire:click="openForm">Registrar OC recibida</x-primary-button>
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
            <x-input-label value="Origen" />
            <select wire:model.live="originFilter"
                    class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-sm">
                <option value="">Todos</option>
                <option value="generada">Generada desde cotización</option>
                <option value="recibida">Recibida del cliente</option>
            </select>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
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
            <tbody class="divide-y divide-slate-100">
                @forelse ($orders as $order)
                    <tr wire:key="po-{{ $order->id }}" class="transition hover:bg-slate-50/70">
                        <td class="px-4 py-3 font-mono">{{ $order->number }}</td>
                        <td class="px-4 py-3">{{ $order->client->business_name }}</td>
                        <td class="px-4 py-3">{{ $order->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-right">S/ {{ number_format((float) $order->amount, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $order->origin === \App\Enums\PurchaseOrderOrigin::Generated ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                {{ $order->origin->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-mono">
                            @if ($order->quotation)
                                <a href="{{ route('cotizaciones.ver', $order->quotation) }}" wire:navigate class="font-medium text-brand-700 hover:text-brand-800 hover:underline">
                                    {{ $order->quotation->code }}
                                </a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">{{ $order->dispatch_guides_count }} / {{ $order->invoices_count }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($order->pdf_path)
                                <a href="{{ route('ordenes-compra.pdf', $order) }}" class="font-medium text-brand-700 hover:text-brand-800 hover:underline">Descargar PDF</a>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center">
                            <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg>
                            <p class="mt-2 text-sm text-slate-400">Sin órdenes de compra que mostrar.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pt-1">{{ $orders->links() }}</div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" wire:click="$set('showForm', false)"></div>
            <div class="relative max-h-[92vh] w-full overflow-y-auto rounded-t-2xl bg-white p-6 shadow-2xl sm:max-w-lg sm:rounded-2xl space-y-4">
                <h3 class="text-lg font-semibold text-slate-800">Registrar OC recibida del cliente</h3>

                <div>
                    <x-input-label value="Cliente" />
                    <select wire:model="client_id"
                            class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-sm">
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
                           class="mt-1 block w-full text-sm text-slate-600 file:me-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-brand-700">
                    <div wire:loading wire:target="pdf" class="text-xs text-slate-500 mt-1">Subiendo…</div>
                    <x-input-error :messages="$errors->get('pdf')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Notas" />
                    <x-text-input type="text" class="mt-1 block w-full" wire:model="notes" />
                    <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                </div>

                <div class="flex flex-wrap justify-end gap-2 pt-2">
                    <x-secondary-button wire:click="$set('showForm', false)">Cancelar</x-secondary-button>
                    <x-primary-button wire:click="save">Registrar</x-primary-button>
                </div>
            </div>
        </div>
    @endif
</x-page>
