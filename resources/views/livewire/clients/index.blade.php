<x-page title="Clientes">
    <x-slot name="actions">
        @can('clients.manage')
            <x-primary-button wire:click="openCreate">Nuevo cliente</x-primary-button>
        @endcan
    </x-slot>

    <div class="bg-white shadow-sm sm:rounded-lg p-4">
        <x-input-label value="Buscar" />
        <x-text-input type="text" class="mt-1 block w-full md:w-96" placeholder="Razón social o RUC…"
                      wire:model.live.debounce.300ms="search" />
    </div>

    <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3">RUC</th>
                    <th class="px-4 py-3">Razón social</th>
                    <th class="px-4 py-3">Distrito</th>
                    <th class="px-4 py-3">Contacto</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($clients as $client)
                    <tr wire:key="client-{{ $client->id }}">
                        <td class="px-4 py-3 font-mono">{{ $client->ruc }}</td>
                        <td class="px-4 py-3">{{ $client->business_name }}</td>
                        <td class="px-4 py-3">{{ $client->district ?? '—' }}</td>
                        <td class="px-4 py-3">
                            {{ $client->contact_name ?? '—' }}
                            @if ($client->phone)
                                <span class="text-gray-400">· {{ $client->phone }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs {{ $client->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">
                                {{ $client->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                            @can('clients.manage')
                                <button wire:click="openEdit({{ $client->id }})" class="text-indigo-600 hover:underline">Editar</button>
                                <button wire:click="toggleActive({{ $client->id }})" class="text-amber-600 hover:underline">
                                    {{ $client->is_active ? 'Desactivar' : 'Activar' }}
                                </button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Sin clientes que mostrar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $clients->links() }}</div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-gray-900/50" wire:click="$set('showForm', false)"></div>
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-2xl p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold text-gray-800">
                    {{ $editingId ? 'Editar cliente' : 'Nuevo cliente' }}
                </h3>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <x-input-label value="RUC" />
                        <x-text-input type="text" maxlength="11" class="mt-1 block w-full" wire:model="ruc" />
                        <x-input-error :messages="$errors->get('ruc')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label value="Razón social" />
                        <x-text-input type="text" class="mt-1 block w-full" wire:model="business_name" />
                        <x-input-error :messages="$errors->get('business_name')" class="mt-1" />
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label value="Dirección fiscal" />
                        <x-text-input type="text" class="mt-1 block w-full" wire:model="address" />
                        <x-input-error :messages="$errors->get('address')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label value="Distrito" />
                        <x-text-input type="text" class="mt-1 block w-full" wire:model="district" />
                        <x-input-error :messages="$errors->get('district')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label value="Teléfono" />
                        <x-text-input type="text" class="mt-1 block w-full" wire:model="phone" />
                        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label value="Correo" />
                        <x-text-input type="email" class="mt-1 block w-full" wire:model="email" />
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label value="Persona de contacto" />
                        <x-text-input type="text" class="mt-1 block w-full" wire:model="contact_name" />
                        <x-input-error :messages="$errors->get('contact_name')" class="mt-1" />
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <x-secondary-button wire:click="$set('showForm', false)">Cancelar</x-secondary-button>
                    <x-primary-button wire:click="save">Guardar</x-primary-button>
                </div>
            </div>
        </div>
    @endif
</x-page>
