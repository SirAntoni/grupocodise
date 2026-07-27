<x-page title="Reporte quincenal de guías por empresa">
    <x-slot name="actions">
        <a href="{{ route('reportes.guias.excel', ['clientId' => $clientId, 'year' => $year, 'month' => $month, 'fortnight' => $fortnight, 'includeAnnulled' => $includeAnnulled ? 1 : 0]) }}">
            <x-primary-button>Exportar a Excel</x-primary-button>
        </a>
    </x-slot>

    <div class="bg-white shadow-sm sm:rounded-lg p-4 grid gap-3 md:grid-cols-5 items-end">
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
            <x-input-label value="Año" />
            <select wire:model.live="year"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                @for ($y = now()->year; $y >= now()->year - 4; $y--)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endfor
            </select>
        </div>
        <div>
            <x-input-label value="Mes" />
            <select wire:model.live="month"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                @foreach (['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] as $i => $name)
                    <option value="{{ $i + 1 }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label value="Quincena" />
            <select wire:model.live="fortnight"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="1">1.ª (del 1 al 15)</option>
                <option value="2">2.ª (del 16 a fin de mes)</option>
            </select>
        </div>
        <label class="inline-flex items-center gap-2 text-sm pb-2">
            <input type="checkbox" wire:model.live="includeAnnulled"
                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            Mostrar anuladas
        </label>
    </div>

    <div class="text-sm text-gray-500">
        Periodo: <strong>{{ $start->format('d/m/Y') }} — {{ $end->format('d/m/Y') }}</strong>
        · {{ $guides->count() }} guías
    </div>

    <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3">Guía</th>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Cliente</th>
                    <th class="px-4 py-3">Requerimiento</th>
                    <th class="px-4 py-3">Producto</th>
                    <th class="px-4 py-3 text-right">Solicitada</th>
                    <th class="px-4 py-3 text-right">Despachada</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3">Factura</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($guides as $guide)
                    @foreach ($guide->items as $item)
                        <tr wire:key="rep-{{ $guide->id }}-{{ $item->id }}" class="{{ $guide->status === \App\Enums\DispatchGuideStatus::Annulled ? 'bg-red-50/50 text-gray-400' : '' }}">
                            <td class="px-4 py-2 font-mono">
                                @if ($loop->first)
                                    <a href="{{ route('guias.ver', $guide) }}" wire:navigate class="text-indigo-600 hover:underline">
                                        {{ $guide->full_number }}
                                    </a>
                                @endif
                            </td>
                            <td class="px-4 py-2">{{ $loop->first ? $guide->issue_date?->format('d/m/Y') : '' }}</td>
                            <td class="px-4 py-2">{{ $loop->first ? $guide->client->business_name : '' }}</td>
                            <td class="px-4 py-2 font-mono">{{ $loop->first ? ($guide->requirement?->code ?? '—') : '' }}</td>
                            <td class="px-4 py-2">{{ $item->description }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format((float) $item->quantity_requested, 2) }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format((float) $item->quantity_dispatched, 2) }}</td>
                            <td class="px-4 py-2">
                                @if ($loop->first)
                                    <span class="px-2 py-0.5 rounded-full text-xs {{ $guide->status->badgeColor() }}">
                                        {{ $guide->status->label() }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-2 font-mono">{{ $loop->first ? $guide->invoices->pluck('full_number')->implode(', ') : '' }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">Sin guías en el periodo seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-page>
