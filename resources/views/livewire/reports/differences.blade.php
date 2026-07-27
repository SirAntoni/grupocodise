<x-page title="Diferencias solicitado vs. despachado">
    <x-slot name="actions">
        <a href="{{ route('reportes.diferencias.excel', ['clientId' => $clientId, 'from' => $from, 'until' => $until]) }}">
            <x-primary-button>Exportar a Excel</x-primary-button>
        </a>
    </x-slot>

    <div class="bg-white shadow-sm sm:rounded-lg p-4 grid gap-3 md:grid-cols-3">
        <div>
            <x-input-label value="Empresa" />
            <select wire:model.live="clientId"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">Todas</option>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->business_name }}</option>
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

    <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3">Código</th>
                    <th class="px-4 py-3">Producto</th>
                    <th class="px-4 py-3">Unidad</th>
                    <th class="px-4 py-3 text-right">Solicitado</th>
                    <th class="px-4 py-3 text-right">Despachado</th>
                    <th class="px-4 py-3 text-right">Diferencia</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($rows as $row)
                    <tr wire:key="diff-{{ $row->product->id }}">
                        <td class="px-4 py-3 font-mono">{{ $row->product->code }}</td>
                        <td class="px-4 py-3">{{ $row->product->name }}</td>
                        <td class="px-4 py-3">{{ $row->product->unit_code }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($row->requested, 2) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($row->dispatched, 2) }}</td>
                        <td class="px-4 py-3 text-right font-medium {{ $row->difference != 0 ? 'text-amber-600' : 'text-gray-400' }}">
                            {{ number_format($row->difference, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Sin despachos en el periodo seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-page>
