<x-page :title="'Guía '.($guide->full_number ?? 'en borrador #'.$guide->id)">
    <x-slot name="actions">
        @if ($guide->isDraft())
            @can('guides.manage')
                <x-danger-button @click="$store.confirm.open('¿Eliminar este borrador de guía?', () => $wire.deleteDraft(), { danger: true, confirmText: 'Sí, eliminar' })">Eliminar borrador</x-danger-button>
                <a href="{{ route('guias.editar', ['dispatchGuide' => $guide->id]) }}" wire:navigate>
                    <x-secondary-button>Editar</x-secondary-button>
                </a>
                <x-primary-button @click="$store.confirm.open('Al emitir se asignará numeración correlativa y se descontará el stock despachado.', () => $wire.issue(), { title: 'Emitir guía', confirmText: 'Sí, emitir' })">
                    Emitir guía
                </x-primary-button>
            @endcan
        @else
            <a href="{{ route('guias.pdf', ['dispatchGuide' => $guide->id, 'copy' => 'cliente']) }}" target="_blank">
                <x-secondary-button>PDF copia cliente</x-secondary-button>
            </a>
            <a href="{{ route('guias.pdf', ['dispatchGuide' => $guide->id, 'copy' => 'empresa']) }}" target="_blank">
                <x-secondary-button>PDF copia empresa</x-secondary-button>
            </a>
            <a href="{{ route('guias.pdf', ['dispatchGuide' => $guide->id]) }}" target="_blank">
                <x-primary-button>PDF 2 copias</x-primary-button>
            </a>
            @can('resend', $guide)
                <x-secondary-button wire:click="resend">Reenviar a SUNAT</x-secondary-button>
            @endcan
            @can('guides.manage')
                <x-secondary-button wire:click="duplicate">Duplicar</x-secondary-button>
                @if ($guide->isIssued())
                    <x-danger-button wire:click="$set('showAnnulForm', true)">Anular</x-danger-button>
                @endif
            @endcan
        @endif
    </x-slot>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-6 grid sm:grid-cols-2 md:grid-cols-3 gap-4 text-sm">
        <div>
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Cliente</div>
            <div class="mt-1 font-medium text-slate-900">{{ $guide->client->business_name }}</div>
            <div class="text-slate-500 font-mono">{{ $guide->client->ruc }}</div>
        </div>
        <div>
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500 mb-1">Estado</div>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $guide->status->badgeColor() }}">{{ $guide->status->label() }}</span>
            @if ($guide->electronicDocument)
                <div class="mt-1 text-slate-500">SUNAT:
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $guide->electronicDocument->sunat_status->badgeColor() }}">
                        {{ $guide->electronicDocument->sunat_status->label() }}
                    </span>
                </div>
                @if ($guide->electronicDocument->error_message)
                    <div class="mt-1 text-xs text-red-600">{{ $guide->electronicDocument->error_code }}: {{ $guide->electronicDocument->error_message }}</div>
                @endif
            @endif
        </div>
        <div>
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500 mb-1">Requerimiento</div>
            @if ($guide->requirement)
                <a href="{{ route('requerimientos.ver', $guide->requirement) }}" wire:navigate class="font-medium text-brand-600 hover:text-brand-700 hover:underline font-mono">
                    {{ $guide->requirement->code }}
                </a>
            @else
                <span class="text-slate-400">—</span>
            @endif
        </div>
        <div>
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Fecha de emisión</div>
            <div class="mt-1 font-medium text-slate-900">{{ $guide->issue_date?->format('d/m/Y') ?? '—' }}</div>
        </div>
        <div>
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Fecha de traslado</div>
            <div class="mt-1 font-medium text-slate-900">{{ $guide->transfer_date?->format('d/m/Y') ?? '—' }}</div>
        </div>
        <div>
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Jefe de cuadrilla</div>
            <div class="mt-1 font-medium text-slate-900">{{ $guide->crew_chief ?? '—' }}</div>
        </div>
        <div class="sm:col-span-2 md:col-span-2">
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Punto de llegada</div>
            <div class="mt-1 font-medium text-slate-900">{{ $guide->delivery_address ?? '—' }} @if ($guide->district) · {{ $guide->district }} @endif</div>
        </div>
        <div>
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Transporte</div>
            <div class="mt-1 font-medium text-slate-900">
                @if ($guide->transport_mode?->value === 'publico')
                    Público · {{ $guide->carrier_name }} ({{ $guide->carrier_ruc }})
                @elseif ($guide->transport_mode?->value === 'privado')
                    Privado · {{ $guide->vehicle_plate }} · {{ $guide->driver_first_names }} {{ $guide->driver_last_names }}
                @else
                    —
                @endif
            </div>
        </div>
        @if ($guide->status === \App\Enums\DispatchGuideStatus::Annulled)
            <div class="sm:col-span-2 md:col-span-3 rounded-lg bg-red-50 border border-red-200 p-3">
                <div class="text-red-800 text-sm font-medium">Anulada el {{ $guide->annulled_at?->format('d/m/Y H:i') }} por {{ $guide->annulledBy?->name }}</div>
                <div class="text-red-700 text-sm">Motivo: {{ $guide->annulment_reason }}</div>
            </div>
        @endif
        @if ($guide->invoices->isNotEmpty())
            <div class="sm:col-span-2 md:col-span-3">
                <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Facturas asociadas</div>
                <div class="flex gap-2 flex-wrap mt-1">
                    @foreach ($guide->invoices as $invoice)
                        <a href="{{ route('facturas.ver', $invoice) }}" wire:navigate
                           class="font-mono font-medium text-brand-600 hover:text-brand-700 hover:underline text-sm">
                            {{ $invoice->full_number ?? 'Borrador #'.$invoice->id }}
                            <span class="text-slate-400">({{ $invoice->status->label() }})</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3">Producto</th>
                    <th class="px-4 py-3 text-right">Solicitada</th>
                    <th class="px-4 py-3 text-right">Despachada</th>
                    <th class="px-4 py-3 text-right">Diferencia</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($guide->items as $item)
                    <tr wire:key="gitem-{{ $item->id }}" class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3">{{ $item->product->code }} — {{ $item->product->name }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $item->quantity_requested, 2) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $item->quantity_dispatched, 2) }}</td>
                        <td class="px-4 py-3 text-right {{ $item->difference() != 0 ? 'text-amber-600 font-medium' : 'text-slate-400' }}">
                            {{ number_format($item->difference(), 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($showAnnulForm)
        <div class="fixed inset-0 z-[80] flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" wire:click="$set('showAnnulForm', false)"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg p-6 space-y-4">
                <h3 class="text-lg font-semibold text-slate-800">Anular guía {{ $guide->full_number }}</h3>
                <p class="text-sm text-slate-500">Se restituirá el stock de todos los ítems despachados.</p>
                <div>
                    <x-input-label value="Motivo de anulación" />
                    <textarea wire:model="annulment_reason" rows="3"
                              class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm"></textarea>
                    <x-input-error :messages="$errors->get('annulment_reason')" class="mt-1" />
                </div>
                <div class="flex flex-wrap justify-end gap-2">
                    <x-secondary-button wire:click="$set('showAnnulForm', false)">Cancelar</x-secondary-button>
                    <x-danger-button wire:click="annul">Confirmar anulación</x-danger-button>
                </div>
            </div>
        </div>
    @endif
</x-page>
