<x-page title="Kardex de stock">
    <x-slot name="actions">
        @can('stock.manage')
            <x-secondary-button wire:click="openMovementForm('ajuste')">Registrar ajuste</x-secondary-button>
            <x-primary-button wire:click="openMovementForm('entrada')">Registrar entrada</x-primary-button>
        @endcan
    </x-slot>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200 p-4 grid gap-3 md:grid-cols-4">
        <div>
            <x-input-label value="Producto" />
            <select wire:model.live="productId"
                    class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm">
                <option value="">Todos</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->code }} — {{ $product->name }} (stock: {{ number_format((float) $product->stock, 2) }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label value="Tipo de movimiento" />
            <select wire:model.live="typeFilter"
                    class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm">
                <option value="">Todos</option>
                @foreach ($types as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label value="Desde" />
            <x-text-input type="date" class="mt-1 block w-full" wire:model.live="from" />
        </div>
        <div>
            <x-input-label value="Hasta" />
            <x-text-input type="date" class="mt-1 block w-full" wire:model.live="until" />
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Producto</th>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3 text-right">Cantidad</th>
                    <th class="px-4 py-3 text-right">Stock anterior</th>
                    <th class="px-4 py-3 text-right">Stock posterior</th>
                    <th class="px-4 py-3">Referencia</th>
                    <th class="px-4 py-3">Usuario</th>
                    <th class="px-4 py-3">Observación</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($movements as $movement)
                    <tr wire:key="mov-{{ $movement->id }}" class="transition hover:bg-slate-50/70">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $movement->product->code }} — {{ $movement->product->name }}</td>
                        <td class="px-4 py-3">{{ $movement->type->label() }}</td>
                        <td class="px-4 py-3 text-right {{ $movement->type === \App\Enums\StockMovementType::DispatchExit || (float) $movement->quantity < 0 ? 'text-red-600' : 'text-green-700' }}">
                            {{ $movement->type === \App\Enums\StockMovementType::DispatchExit ? '−' : '' }}{{ number_format(abs((float) $movement->quantity), 2) }}
                        </td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $movement->stock_before, 2) }}</td>
                        <td class="px-4 py-3 text-right font-medium">{{ number_format((float) $movement->stock_after, 2) }}</td>
                        <td class="px-4 py-3">
                            @if ($movement->reference?->full_number ?? false)
                                <span class="font-mono">{{ $movement->reference->full_number }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $movement->user?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $movement->notes ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center">
                            <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5"/></svg>
                            <p class="mt-2 text-sm text-slate-400">Sin movimientos que mostrar.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $movements->links() }}</div>

    @if ($showMovementForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-slate-900/50" wire:click="$set('showMovementForm', false)"></div>
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold text-slate-800">
                    {{ $movementKind === 'entrada' ? 'Registrar entrada de stock' : 'Registrar ajuste de stock' }}
                </h3>

                <div>
                    <x-input-label value="Producto" />
                    <select wire:model="movementProductId"
                            class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm">
                        <option value="">Seleccione…</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->code }} — {{ $product->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('movementProductId')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Cantidad" />
                    <x-text-input type="number" step="0.01" class="mt-1 block w-full" wire:model="quantity" />
                    <x-input-error :messages="$errors->get('quantity')" class="mt-1" />
                    @if ($movementKind === 'ajuste')
                        <p class="mt-1 text-xs text-slate-500">Usa cantidad negativa para disminuir el stock.</p>
                    @endif
                </div>
                <div>
                    <x-input-label value="Observación (opcional)" />
                    <x-text-input type="text" class="mt-1 block w-full" wire:model="notes" />
                    <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <x-secondary-button wire:click="$set('showMovementForm', false)">Cancelar</x-secondary-button>
                    <x-primary-button wire:click="saveMovement">Guardar</x-primary-button>
                </div>
            </div>
        </div>
    @endif
</x-page>
