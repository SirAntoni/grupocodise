<x-page title="Productos">
    <x-slot name="actions">
        @can('products.manage')
            <x-primary-button wire:click="openCreate">Nuevo producto</x-primary-button>
        @endcan
    </x-slot>

    <div class="bg-white shadow-sm sm:rounded-lg p-4 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-56">
            <x-input-label value="Buscar" />
            <x-text-input type="text" class="mt-1 block w-full" placeholder="Código o nombre…"
                          wire:model.live.debounce.300ms="search" />
        </div>
        <div>
            <x-input-label value="Estado" />
            <select wire:model.live="statusFilter"
                    class="mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="activos">Activos</option>
                <option value="inactivos">Inactivos</option>
                <option value="todos">Todos</option>
            </select>
        </div>
    </div>

    <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3">Código</th>
                    <th class="px-4 py-3">Nombre</th>
                    <th class="px-4 py-3">Unidad</th>
                    <th class="px-4 py-3 text-right">Stock</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($products as $product)
                    <tr wire:key="product-{{ $product->id }}">
                        <td class="px-4 py-3 font-mono">{{ $product->code }}</td>
                        <td class="px-4 py-3">{{ $product->name }}</td>
                        <td class="px-4 py-3">{{ \App\Support\SunatCatalogs::unitLabel($product->unit_code) }}</td>
                        <td class="px-4 py-3 text-right {{ (float) $product->stock <= 0 ? 'text-red-600 font-semibold' : '' }}">
                            {{ number_format((float) $product->stock, 2) }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">
                                {{ $product->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                            @can('stock.view')
                                <a href="{{ route('kardex.index', ['productId' => $product->id]) }}" wire:navigate
                                   class="text-indigo-600 hover:underline">Kardex</a>
                            @endcan
                            @can('products.manage')
                                <button wire:click="openEdit({{ $product->id }})" class="text-indigo-600 hover:underline">Editar</button>
                                <button wire:click="toggleActive({{ $product->id }})" class="text-amber-600 hover:underline">
                                    {{ $product->is_active ? 'Desactivar' : 'Activar' }}
                                </button>
                                <button wire:click="delete({{ $product->id }})"
                                        wire:confirm="¿Eliminar el producto {{ $product->code }}?"
                                        class="text-red-600 hover:underline">Eliminar</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Sin productos que mostrar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $products->links() }}</div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-gray-900/50" wire:click="$set('showForm', false)"></div>
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-800">
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
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
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
                        <p class="mt-1 text-xs text-gray-500">Se registrará como entrada en el kardex.</p>
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
