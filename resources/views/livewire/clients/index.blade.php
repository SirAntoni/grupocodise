<x-page title="Clientes">
    <x-slot name="actions">
        @can('clients.manage')
            <x-primary-button wire:click="openCreate">Nuevo cliente</x-primary-button>
        @endcan
    </x-slot>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200 p-4">
        <x-input-label value="Buscar" />
        <x-text-input type="text" class="mt-1 block w-full md:w-96" placeholder="Razón social o RUC…"
                      wire:model.live.debounce.300ms="search" />
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">RUC</th>
                    <th class="px-4 py-3">Razón social</th>
                    <th class="px-4 py-3">Distrito</th>
                    <th class="px-4 py-3">Contacto</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($clients as $client)
                    <tr wire:key="client-{{ $client->id }}" class="transition hover:bg-slate-50/70">
                        <td class="px-4 py-3 font-mono">{{ $client->ruc }}</td>
                        <td class="px-4 py-3">{{ $client->business_name }}</td>
                        <td class="px-4 py-3">{{ $client->district ?? '—' }}</td>
                        <td class="px-4 py-3">
                            {{ $client->contact_name ?? '—' }}
                            @if ($client->phone)
                                <span class="text-slate-400">· {{ $client->phone }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $client->is_active ? 'bg-green-100 text-green-800' : 'bg-slate-200 text-slate-600' }}">
                                {{ $client->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                            @can('clients.manage')
                                <button wire:click="openEdit({{ $client->id }})" class="font-medium text-brand-700 hover:text-brand-800 hover:underline">Editar</button>
                                <button wire:click="toggleActive({{ $client->id }})" class="font-medium text-amber-600 hover:text-amber-700 hover:underline">
                                    {{ $client->is_active ? 'Desactivar' : 'Activar' }}
                                </button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center">
                            <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg>
                            <p class="mt-2 text-sm text-slate-400">Sin clientes que mostrar.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pt-1">{{ $clients->links() }}</div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" wire:click="$set('showForm', false)"></div>
            <div class="relative max-h-[92vh] w-full overflow-y-auto rounded-t-2xl bg-white p-6 shadow-2xl sm:max-w-2xl sm:rounded-2xl space-y-4">
                <h3 class="text-lg font-semibold text-slate-800">
                    {{ $editingId ? 'Editar cliente' : 'Nuevo cliente' }}
                </h3>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <x-input-label value="RUC" />
                        <div class="mt-1 flex gap-2">
                            <x-text-input type="text" maxlength="11" class="block w-full" wire:model="ruc"
                                          wire:keydown.enter="lookupRuc" />
                            <x-secondary-button wire:click="lookupRuc" wire:loading.attr="disabled" wire:target="lookupRuc" title="Buscar en el padrón SUNAT">
                                <span wire:loading.remove wire:target="lookupRuc">Buscar</span>
                                <span wire:loading wire:target="lookupRuc">…</span>
                            </x-secondary-button>
                        </div>
                        <x-input-error :messages="$errors->get('ruc')" class="mt-1" />
                        @if ($rucInfo)
                            <div class="mt-2 flex flex-wrap items-center gap-1.5 text-xs">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 font-medium {{ $rucInfo['estado'] === 'ACTIVO' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $rucInfo['estado'] ?? 'Estado desconocido' }}
                                </span>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 font-medium {{ $rucInfo['condicion'] === 'HABIDO' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $rucInfo['condicion'] ?? 'Condición desconocida' }}
                                </span>
                                <span class="text-slate-400">según padrón SUNAT</span>
                            </div>
                        @endif
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
                        <x-input-label value="Ubigeo (para la GRE)" />
                        <x-text-input type="text" maxlength="6" class="mt-1 block w-full font-mono" placeholder="150101" wire:model="ubigeo" />
                        <x-input-error :messages="$errors->get('ubigeo')" class="mt-1" />
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
