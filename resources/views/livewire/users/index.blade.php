<x-page title="Usuarios">
    <x-slot name="actions">
        <x-primary-button wire:click="openCreate">Nuevo usuario</x-primary-button>
    </x-slot>

    <div class="bg-white shadow-sm sm:rounded-lg p-4">
        <x-input-label value="Buscar" />
        <x-text-input type="text" class="mt-1 block w-full md:w-96" placeholder="Nombre o correo…"
                      wire:model.live.debounce.300ms="search" />
    </div>

    <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3">Nombre</th>
                    <th class="px-4 py-3">Correo</th>
                    <th class="px-4 py-3">Rol</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                        <td class="px-4 py-3">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs bg-indigo-100 text-indigo-800">
                                {{ $user->getRoleNames()->first() ?? 'sin rol' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">
                                {{ $user->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                            <button wire:click="openEdit({{ $user->id }})" class="text-indigo-600 hover:underline">Editar</button>
                            @if ($user->id !== auth()->id())
                                <button wire:click="toggleActive({{ $user->id }})" class="text-amber-600 hover:underline">
                                    {{ $user->is_active ? 'Desactivar' : 'Activar' }}
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">Sin usuarios que mostrar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $users->links() }}</div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-gray-900/50" wire:click="$set('showForm', false)"></div>
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-800">
                    {{ $editingId ? 'Editar usuario' : 'Nuevo usuario' }}
                </h3>

                <div>
                    <x-input-label value="Nombre" />
                    <x-text-input type="text" class="mt-1 block w-full" wire:model="name" />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Correo" />
                    <x-text-input type="email" class="mt-1 block w-full" wire:model="email" />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>
                <div>
                    <x-input-label :value="$editingId ? 'Contraseña (en blanco = no cambiar)' : 'Contraseña'" />
                    <x-text-input type="password" class="mt-1 block w-full" wire:model="password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Rol" />
                    <select wire:model="role"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        <option value="">Seleccione…</option>
                        @foreach ($roles as $roleName)
                            <option value="{{ $roleName }}">{{ $roleName }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role')" class="mt-1" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <x-secondary-button wire:click="$set('showForm', false)">Cancelar</x-secondary-button>
                    <x-primary-button wire:click="save">Guardar</x-primary-button>
                </div>
            </div>
        </div>
    @endif
</x-page>
