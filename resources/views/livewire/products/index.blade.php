<x-page title="Productos">
    <x-slot name="actions">
        @can('products.manage')
            <x-primary-button wire:click="openCreate">Nuevo producto</x-primary-button>
        @endcan
    </x-slot>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200 p-4 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-56">
            <x-input-label value="Buscar" />
            <x-text-input type="text" class="mt-1 block w-full" placeholder="Código o nombre…"
                          wire:model.live.debounce.300ms="search" />
        </div>
        <div>
            <x-input-label value="Estado" />
            <select wire:model.live="statusFilter"
                    class="mt-1 border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm">
                <option value="activos">Activos</option>
                <option value="inactivos">Inactivos</option>
                <option value="todos">Todos</option>
            </select>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Código</th>
                    <th class="px-4 py-3">Nombre</th>
                    <th class="px-4 py-3">Unidad</th>
                    <th class="px-4 py-3 text-right">Stock</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($products as $product)
                    <tr wire:key="product-{{ $product->id }}" class="transition hover:bg-slate-50/70">
                        <td class="px-4 py-3 font-mono">{{ $product->code }}</td>
                        <td class="px-4 py-3">{{ $product->name }}</td>
                        <td class="px-4 py-3">{{ \App\Support\SunatCatalogs::unitLabel($product->unit_code) }}</td>
                        <td class="px-4 py-3 text-right {{ (float) $product->stock <= 0 ? 'text-red-600 font-semibold' : '' }}">
                            {{ number_format((float) $product->stock, 2) }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-slate-200 text-slate-600' }}">
                                {{ $product->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                            @can('stock.view')
                                <a href="{{ route('kardex.index', ['productId' => $product->id]) }}" wire:navigate
                                   class="font-medium text-brand-700 hover:text-brand-800 hover:underline">Kardex</a>
                            @endcan
                            @can('products.manage')
                                <button wire:click="openEdit({{ $product->id }})" class="font-medium text-brand-700 hover:text-brand-800 hover:underline">Editar</button>
                                <button wire:click="toggleActive({{ $product->id }})" class="font-medium text-amber-600 hover:text-amber-700 hover:underline">
                                    {{ $product->is_active ? 'Desactivar' : 'Activar' }}
                                </button>
                                <button @click="$store.confirm.open('¿Eliminar el producto {{ $product->code }}? Esta acción no se puede deshacer.', () => $wire.delete({{ $product->id }}), { danger: true, confirmText: 'Sí, eliminar' })"
                                        class="font-medium text-red-600 hover:text-red-700 hover:underline">Eliminar</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center">
                            <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                            <p class="mt-2 text-sm text-slate-400">Sin productos que mostrar.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $products->links() }}</div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-slate-900/50" wire:click="$set('showForm', false)"></div>
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold text-slate-800">
                    {{ $editingId ? 'Editar producto' : 'Nuevo producto' }}
                </h3>

                <div>
                    <x-input-label for="code" value="Código" />
                    <x-text-input id="code" type="text" class="mt-1 block w-full" wire:model="code" />
                    <x-input-error :messages="$errors->get('code')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="name" value="Nombre" />
                    <x-text-input id="name" type="text" class="mt-1 block w-full" wire:model="name" />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="unit_code" value="Unidad de medida" />
                    <select id="unit_code" wire:model="unit_code"
                            class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm">
                        @foreach ($units as $unitCode => $label)
                            <option value="{{ $unitCode }}">{{ $label }} ({{ $unitCode }})</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('unit_code')" class="mt-1" />
                </div>
                @unless ($editingId)
                    <div>
                        <x-input-label for="initial_stock" value="Stock inicial (opcional)" />
                        <x-text-input id="initial_stock" type="number" step="0.01" min="0" class="mt-1 block w-full"
                                      wire:model="initial_stock" />
                        <x-input-error :messages="$errors->get('initial_stock')" class="mt-1" />
                        <p class="mt-1 text-xs text-slate-500">Se registrará como entrada en el kardex.</p>
                    </div>
                @endunless

                <div class="flex justify-end gap-2 pt-2">
                    <x-secondary-button wire:click="$set('showForm', false)">Cancelar</x-secondary-button>
                    <x-primary-button wire:click="save">Guardar</x-primary-button>
                </div>
            </div>
        </div>
    @endif
</x-page>
