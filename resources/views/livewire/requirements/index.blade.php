<x-page title="Requerimientos">
    <x-slot name="actions">
        @can('requirements.manage')
            <a href="{{ route('requerimientos.crear') }}" wire:navigate>
                <x-primary-button>Nuevo requerimiento</x-primary-button>
            </a>
        @endcan
    </x-slot>

    <div class="bg-white shadow-sm sm:rounded-lg p-4 grid gap-3 md:grid-cols-4">
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
            <x-input-label value="Estado" />
            <select wire:model.live="statusFilter"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">Todos</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label value="Fecha requerida desde" />
            <x-text-input type="date" class="mt-1 block w-full" wire:model.live="from" />
        </div>
        <div>
            <x-input-label value="Fecha requerida hasta" />
            <x-text-input type="date" class="mt-1 block w-full" wire:model.live="until" />
        </div>
    </div>

    <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3">Código</th>
                    <th class="px-4 py-3">Cliente</th>
                    <th class="px-4 py-3">Jefe de cuadrilla</th>
                    <th class="px-4 py-3">Distrito</th>
                    <th class="px-4 py-3">Fecha requerida</th>
                    <th class="px-4 py-3 text-center">Ítems</th>
                    <th class="px-4 py-3 text-center">Guías</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($requirements as $requirement)
                    <tr wire:key="req-{{ $requirement->id }}">
                        <td class="px-4 py-3 font-mono">
                            <a href="{{ route('requerimientos.ver', $requirement) }}" wire:navigate class="text-indigo-600 hover:underline">
                                {{ $requirement->code }}
                            </a>
                        </td>
                        <td class="px-4 py-3">{{ $requirement->client->business_name }}</td>
                        <td class="px-4 py-3">{{ $requirement->crew_chief }}</td>
                        <td class="px-4 py-3">{{ $requirement->district }}</td>
                        <td class="px-4 py-3">{{ $requirement->required_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-center">{{ $requirement->items->count() }}</td>
                        <td class="px-4 py-3 text-center">{{ $requirement->dispatch_guides_count }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs {{ $requirement->status->badgeColor() }}">
                                {{ $requirement->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                            @if ($requirement->isEditable())
                                @can('requirements.manage')
                                    <a href="{{ route('requerimientos.editar', $requirement) }}" wire:navigate class="text-indigo-600 hover:underline">Editar</a>
                                    <button wire:click="annul({{ $requirement->id }})"
                                            wire:confirm="¿Anular el requerimiento {{ $requirement->code }}?"
                                            class="text-red-600 hover:underline">Anular</button>
                                @endcan
                                @can('guides.manage')
                                    <button wire:click="createGuide({{ $requirement->id }})" class="text-green-700 hover:underline">Generar guía</button>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">Sin requerimientos que mostrar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $requirements->links() }}</div>
</x-page>
