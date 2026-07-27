<x-page title="Series de comprobantes">
    <x-slot name="actions">
        <x-primary-button wire:click="openCreate">Nueva serie</x-primary-button>
    </x-slot>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Tipo de documento</th>
                    <th class="px-4 py-3">Serie</th>
                    <th class="px-4 py-3 text-right">Siguiente correlativo</th>
                    <th class="px-4 py-3">Ambiente</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($allSeries as $series)
                    <tr wire:key="series-{{ $series->id }}" class="transition hover:bg-slate-50/70">
                        <td class="px-4 py-3">{{ $series->document_type->label() }}</td>
                        <td class="px-4 py-3 font-mono font-medium">{{ $series->code }}</td>
                        <td class="px-4 py-3 text-right font-mono">{{ str_pad($series->next_number, 8, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $series->environment === 'produccion' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $series->environment === 'produccion' ? 'Producción' : 'Beta (pruebas)' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $series->is_active ? 'bg-green-100 text-green-800' : 'bg-slate-200 text-slate-600' }}">
                                {{ $series->is_active ? 'Activa' : 'Inactiva' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="toggleActive({{ $series->id }})" class="font-medium text-amber-600 hover:text-amber-700 hover:underline">
                                {{ $series->is_active ? 'Desactivar' : 'Activar' }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center">
                            <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg>
                            <p class="mt-2 text-sm text-slate-400">Sin series registradas.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-slate-500">
        El correlativo avanza automáticamente al emitir y no se puede editar: los comprobantes electrónicos exigen numeración continua.
        Para el pase a producción, registra series nuevas (ambiente producción) y desactiva las de pruebas.
    </p>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" wire:click="$set('showForm', false)"></div>
            <div class="relative max-h-[92vh] w-full overflow-y-auto rounded-t-2xl bg-white p-6 shadow-2xl sm:max-w-lg sm:rounded-2xl space-y-4">
                <h3 class="text-lg font-semibold text-slate-800">Nueva serie ({{ config('facturacion.environment') === 'produccion' ? 'producción' : 'beta' }})</h3>

                <div>
                    <x-input-label value="Tipo de documento" />
                    <select wire:model="document_type"
                            class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-sm">
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('document_type')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Serie (4 caracteres: T001, F002, FC01…)" />
                    <x-text-input type="text" maxlength="4" class="mt-1 block w-full font-mono uppercase" wire:model="code" />
                    <x-input-error :messages="$errors->get('code')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Siguiente correlativo" />
                    <x-text-input type="number" min="1" class="mt-1 block w-full" wire:model="next_number" />
                    <x-input-error :messages="$errors->get('next_number')" class="mt-1" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <x-secondary-button wire:click="$set('showForm', false)">Cancelar</x-secondary-button>
                    <x-primary-button wire:click="save">Guardar</x-primary-button>
                </div>
            </div>
        </div>
    @endif
</x-page>
