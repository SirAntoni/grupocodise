<x-page title="Series de comprobantes">
    <x-slot name="actions">
        <x-primary-button wire:click="openCreate">Nueva serie</x-primary-button>
    </x-slot>

    <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3">Tipo de documento</th>
                    <th class="px-4 py-3">Serie</th>
                    <th class="px-4 py-3 text-right">Siguiente correlativo</th>
                    <th class="px-4 py-3">Ambiente</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($allSeries as $series)
                    <tr wire:key="series-{{ $series->id }}">
                        <td class="px-4 py-3">{{ $series->document_type->label() }}</td>
                        <td class="px-4 py-3 font-mono font-medium">{{ $series->code }}</td>
                        <td class="px-4 py-3 text-right font-mono">{{ str_pad($series->next_number, 8, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs {{ $series->environment === 'produccion' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $series->environment === 'produccion' ? 'Producción' : 'Beta (pruebas)' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs {{ $series->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">
                                {{ $series->is_active ? 'Activa' : 'Inactiva' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="toggleActive({{ $series->id }})" class="text-amber-600 hover:underline">
                                {{ $series->is_active ? 'Desactivar' : 'Activar' }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Sin series registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-gray-500">
        El correlativo avanza automáticamente al emitir y no se puede editar: los comprobantes electrónicos exigen numeración continua.
        Para el pase a producción, registra series nuevas (ambiente producción) y desactiva las de pruebas.
    </p>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-gray-900/50" wire:click="$set('showForm', false)"></div>
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-800">Nueva serie ({{ config('facturacion.environment') === 'produccion' ? 'producción' : 'beta' }})</h3>

                <div>
                    <x-input-label value="Tipo de documento" />
                    <select wire:model="document_type"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
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
