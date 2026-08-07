{{-- Selector de guías: se usa igual al crear la factura y al agregar más al borrador.
     El contador se lleva en el navegador para que responda al instante; lo que
     vale es lo que marca el checkbox, que Livewire envía al pulsar el botón. --}}
<div x-data="{ marcadas: 0 }"
     x-init="marcadas = $el.querySelectorAll('input[type=checkbox]:checked').length"
     x-on:change="marcadas = $el.querySelectorAll('input[type=checkbox]:checked').length">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h3 class="text-sm font-semibold text-slate-900">{{ $titulo }}</h3>
            <p class="text-xs text-slate-500">Solo se listan las guías emitidas de esta empresa que aún no están en otra factura.</p>
        </div>
        @if ($availableGuides->isNotEmpty() || $guideSearch !== '')
            <div class="w-full sm:w-56">
                <x-text-input type="search" class="block w-full font-mono text-sm" placeholder="Buscar T001-…"
                              wire:model.live.debounce.300ms="guideSearch" />
            </div>
        @endif
    </div>

    <x-input-error :messages="$errors->get('selectedGuides')" class="mt-2" />

    @if ($availableGuides->isEmpty())
        <div class="mt-3 rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">
            @if ($guideSearch !== '')
                Ninguna guía coincide con «{{ $guideSearch }}».
            @else
                Esta empresa no tiene guías emitidas pendientes de facturar.
            @endif
        </div>
    @else
        <div class="mt-3 max-h-96 overflow-y-auto rounded-lg ring-1 ring-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="sticky top-0 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="w-10 px-3 py-2"></th>
                        <th class="px-3 py-2">Guía</th>
                        <th class="px-3 py-2">Fecha</th>
                        <th class="px-3 py-2">Requerimiento</th>
                        <th class="px-3 py-2">Destino</th>
                        <th class="px-3 py-2 text-right">Ítems</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($availableGuides as $guide)
                        <tr wire:key="disp-{{ $guide->id }}" class="transition hover:bg-brand-50/40">
                            <td class="px-3 py-2">
                                <input type="checkbox" value="{{ $guide->id }}" wire:model="selectedGuides"
                                       id="guia-{{ $guide->id }}"
                                       class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            </td>
                            <td class="px-3 py-2 font-mono">
                                <label for="guia-{{ $guide->id }}" class="cursor-pointer">{{ $guide->full_number }}</label>
                            </td>
                            <td class="px-3 py-2">{{ $guide->issue_date?->format('d/m/Y') }}</td>
                            <td class="px-3 py-2 font-mono text-slate-500">{{ $guide->requirement?->code ?? '—' }}</td>
                            <td class="px-3 py-2 text-slate-500">{{ $guide->district ?? '—' }}</td>
                            <td class="px-3 py-2 text-right tabular-nums text-slate-500">{{ $guide->dispatched_items_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($availableGuides->count() >= 100)
            <p class="mt-2 text-xs text-amber-700">Se muestran las 100 guías más recientes. Usa el buscador si necesitas una anterior.</p>
        @endif
    @endif

    {{-- Fuera del @if: si el buscador esconde una guía marcada, el aviso tiene
         que seguir a la vista para que nadie facture algo que no ve. --}}
    <p class="mt-2 text-xs text-slate-500">
        <span x-text="marcadas">0</span> guía(s) marcada(s) a la vista.
        @if ($guideSearch !== '' && count($selectedGuides) > 0)
            <span class="font-medium text-amber-700">
                El buscador está filtrando: en total llevas {{ count($selectedGuides) }} marcada(s).
            </span>
        @endif
    </p>
</div>
