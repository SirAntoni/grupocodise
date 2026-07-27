<x-page :title="'Requerimiento '.$requirement->code">
    <x-slot name="actions">
        @if ($requirement->isEditable())
            @can('requirements.manage')
                <a href="{{ route('requerimientos.editar', $requirement) }}" wire:navigate>
                    <x-secondary-button>Editar</x-secondary-button>
                </a>
                <x-danger-button wire:click="annul" wire:confirm="¿Anular este requerimiento?">Anular</x-danger-button>
            @endcan
            @can('guides.manage')
                <x-primary-button wire:click="createGuide">Generar guía</x-primary-button>
            @endcan
        @endif
    </x-slot>

    <div class="bg-white shadow-sm sm:rounded-lg p-6 grid md:grid-cols-3 gap-4 text-sm">
        <div>
            <div class="text-gray-500">Cliente</div>
            <div class="font-medium">{{ $requirement->client->business_name }}</div>
            <div class="text-gray-500 font-mono">{{ $requirement->client->ruc }}</div>
        </div>
        <div>
            <div class="text-gray-500">Jefe de cuadrilla</div>
            <div class="font-medium">{{ $requirement->crew_chief }}</div>
        </div>
        <div>
            <div class="text-gray-500">Estado</div>
            <span class="px-2 py-0.5 rounded-full text-xs {{ $requirement->status->badgeColor() }}">
                {{ $requirement->status->label() }}
            </span>
        </div>
        <div>
            <div class="text-gray-500">Distrito</div>
            <div class="font-medium">{{ $requirement->district }}</div>
        </div>
        <div>
            <div class="text-gray-500">Fecha requerida</div>
            <div class="font-medium">{{ $requirement->required_date->format('d/m/Y') }}</div>
        </div>
        <div>
            <div class="text-gray-500">Registrado por</div>
            <div class="font-medium">{{ $requirement->createdBy?->name }} · {{ $requirement->created_at->format('d/m/Y H:i') }}</div>
        </div>
        @if ($requirement->delivery_address)
            <div class="md:col-span-3">
                <div class="text-gray-500">Dirección de entrega</div>
                <div class="font-medium">{{ $requirement->delivery_address }}</div>
            </div>
        @endif
        @if ($requirement->notes)
            <div class="md:col-span-3">
                <div class="text-gray-500">Observaciones</div>
                <div>{{ $requirement->notes }}</div>
            </div>
        @endif
    </div>

    <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3">Producto</th>
                    <th class="px-4 py-3">Unidad</th>
                    <th class="px-4 py-3 text-right">Cantidad solicitada</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($requirement->items as $item)
                    <tr wire:key="req-item-{{ $item->id }}">
                        <td class="px-4 py-3">{{ $item->product->code }} — {{ $item->product->name }}</td>
                        <td class="px-4 py-3">{{ \App\Support\SunatCatalogs::unitLabel($item->product->unit_code) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $item->quantity, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-white shadow-sm sm:rounded-lg p-6">
        <h3 class="font-semibold text-gray-800 mb-3">Guías de despacho asociadas</h3>
        @forelse ($requirement->dispatchGuides as $guide)
            <div class="flex items-center justify-between border-b border-gray-100 py-2 text-sm" wire:key="req-guide-{{ $guide->id }}">
                <a href="{{ route('guias.ver', $guide) }}" wire:navigate class="text-indigo-600 hover:underline font-mono">
                    {{ $guide->full_number ?? 'Borrador #'.$guide->id }}
                </a>
                <span>{{ $guide->issue_date?->format('d/m/Y') ?? '—' }}</span>
                <span class="px-2 py-0.5 rounded-full text-xs {{ $guide->status->badgeColor() }}">{{ $guide->status->label() }}</span>
            </div>
        @empty
            <p class="text-sm text-gray-400">Aún no se generan guías para este requerimiento.</p>
        @endforelse
    </div>
</x-page>
