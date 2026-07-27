<x-page title="Guías de despacho">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 grid gap-3 sm:grid-cols-2 md:grid-cols-5">
        <div>
            <x-input-label value="Número" />
            <x-text-input type="text" class="mt-1 block w-full" placeholder="T001-…"
                          wire:model.live.debounce.300ms="search" />
        </div>
        <div>
            <x-input-label value="Cliente" />
            <select wire:model.live="clientFilter"
                    class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm">
                <option value="">Todos</option>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->business_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label value="Estado" />
            <select wire:model.live="statusFilter"
                    class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm">
                <option value="">Todos</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label value="Emitida desde" />
            <x-text-input type="date" class="mt-1 block w-full" wire:model.live="from" />
        </div>
        <div>
            <x-input-label value="Emitida hasta" />
            <x-text-input type="date" class="mt-1 block w-full" wire:model.live="until" />
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3">Número</th>
                    <th class="px-4 py-3">Cliente</th>
                    <th class="px-4 py-3">Requerimiento</th>
                    <th class="px-4 py-3">Fecha emisión</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3">SUNAT</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($guides as $guide)
                    <tr wire:key="guide-{{ $guide->id }}" class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3 font-mono">
                            <a href="{{ route('guias.ver', $guide) }}" wire:navigate class="font-medium text-brand-600 hover:text-brand-700 hover:underline">
                                {{ $guide->full_number ?? 'Borrador #'.$guide->id }}
                            </a>
                        </td>
                        <td class="px-4 py-3">{{ $guide->client->business_name }}</td>
                        <td class="px-4 py-3 font-mono">{{ $guide->requirement?->code ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $guide->issue_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $guide->status->badgeColor() }}">
                                {{ $guide->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if ($guide->electronicDocument)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $guide->electronicDocument->sunat_status->badgeColor() }}">
                                    {{ $guide->electronicDocument->sunat_status->label() }}
                                </span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                            @if ($guide->status !== \App\Enums\DispatchGuideStatus::Draft)
                                <a href="{{ route('guias.pdf', $guide) }}" target="_blank" class="font-medium text-brand-600 hover:text-brand-700 hover:underline">PDF</a>
                            @endif
                            @can('guides.manage')
                                @if ($guide->isDraft())
                                    <a href="{{ route('guias.editar', $guide) }}" wire:navigate class="font-medium text-brand-600 hover:text-brand-700 hover:underline">Editar</a>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">Sin guías que mostrar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $guides->links() }}</div>
</x-page>
