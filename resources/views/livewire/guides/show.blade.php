<x-page :title="'Guía '.($guide->full_number ?? 'en borrador #'.$guide->id)">
    <x-slot name="actions">
        @if ($guide->isDraft())
            @can('guides.manage')
                <x-danger-button wire:click="deleteDraft" wire:confirm="¿Eliminar este borrador?">Eliminar borrador</x-danger-button>
                <a href="{{ route('guias.editar', ['dispatchGuide' => $guide->id]) }}" wire:navigate>
                    <x-secondary-button>Editar</x-secondary-button>
                </a>
                <x-primary-button wire:click="issue" wire:confirm="Al emitir se asignará numeración y se descontará stock. ¿Continuar?">
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

    <div class="bg-white shadow-sm sm:rounded-lg p-6 grid md:grid-cols-3 gap-4 text-sm">
        <div>
            <div class="text-gray-500">Cliente</div>
            <div class="font-medium">{{ $guide->client->business_name }}</div>
            <div class="text-gray-500 font-mono">{{ $guide->client->ruc }}</div>
        </div>
        <div>
            <div class="text-gray-500">Estado</div>
            <span class="px-2 py-0.5 rounded-full text-xs {{ $guide->status->badgeColor() }}">{{ $guide->status->label() }}</span>
            @if ($guide->electronicDocument)
                <div class="mt-1 text-gray-500">SUNAT:
                    <span class="px-2 py-0.5 rounded-full text-xs {{ $guide->electronicDocument->sunat_status->badgeColor() }}">
                        {{ $guide->electronicDocument->sunat_status->label() }}
                    </span>
                </div>
                @if ($guide->electronicDocument->error_message)
                    <div class="mt-1 text-xs text-red-600">{{ $guide->electronicDocument->error_code }}: {{ $guide->electronicDocument->error_message }}</div>
                @endif
            @endif
        </div>
        <div>
            <div class="text-gray-500">Requerimiento</div>
            @if ($guide->requirement)
                <a href="{{ route('requerimientos.ver', $guide->requirement) }}" wire:navigate class="text-indigo-600 hover:underline font-mono">
                    {{ $guide->requirement->code }}
                </a>
            @else
                —
            @endif
        </div>
        <div>
            <div class="text-gray-500">Fecha de emisión</div>
            <div class="font-medium">{{ $guide->issue_date?->format('d/m/Y') ?? '—' }}</div>
        </div>
        <div>
            <div class="text-gray-500">Fecha de traslado</div>
            <div class="font-medium">{{ $guide->transfer_date?->format('d/m/Y') ?? '—' }}</div>
        </div>
        <div>
            <div class="text-gray-500">Jefe de cuadrilla</div>
            <div class="font-medium">{{ $guide->crew_chief ?? '—' }}</div>
        </div>
        <div class="md:col-span-2">
            <div class="text-gray-500">Punto de llegada</div>
            <div class="font-medium">{{ $guide->delivery_address ?? '—' }} @if ($guide->district) · {{ $guide->district }} @endif</div>
        </div>
        <div>
            <div class="text-gray-500">Transporte</div>
            <div class="font-medium">
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
            <div class="md:col-span-3 rounded-md bg-red-50 border border-red-200 p-3">
                <div class="text-red-800 text-sm font-medium">Anulada el {{ $guide->annulled_at?->format('d/m/Y H:i') }} por {{ $guide->annulledBy?->name }}</div>
                <div class="text-red-700 text-sm">Motivo: {{ $guide->annulment_reason }}</div>
            </div>
        @endif
        @if ($guide->invoices->isNotEmpty())
            <div class="md:col-span-3">
                <div class="text-gray-500">Facturas asociadas</div>
                <div class="flex gap-2 flex-wrap mt-1">
                    @foreach ($guide->invoices as $invoice)
                        <a href="{{ route('facturas.ver', $invoice) }}" wire:navigate
                           class="font-mono text-indigo-600 hover:underline text-sm">
                            {{ $invoice->full_number ?? 'Borrador #'.$invoice->id }}
                            <span class="text-gray-400">({{ $invoice->status->label() }})</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3">Producto</th>
                    <th class="px-4 py-3 text-right">Solicitada</th>
                    <th class="px-4 py-3 text-right">Despachada</th>
                    <th class="px-4 py-3 text-right">Diferencia</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($guide->items as $item)
                    <tr wire:key="gitem-{{ $item->id }}">
                        <td class="px-4 py-3">{{ $item->product->code }} — {{ $item->product->name }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $item->quantity_requested, 2) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $item->quantity_dispatched, 2) }}</td>
                        <td class="px-4 py-3 text-right {{ $item->difference() != 0 ? 'text-amber-600 font-medium' : 'text-gray-400' }}">
                            {{ number_format($item->difference(), 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($showAnnulForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-gray-900/50" wire:click="$set('showAnnulForm', false)"></div>
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-800">Anular guía {{ $guide->full_number }}</h3>
                <p class="text-sm text-gray-500">Se restituirá el stock de todos los ítems despachados.</p>
                <div>
                    <x-input-label value="Motivo de anulación" />
                    <textarea wire:model="annulment_reason" rows="3"
                              class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"></textarea>
                    <x-input-error :messages="$errors->get('annulment_reason')" class="mt-1" />
                </div>
                <div class="flex justify-end gap-2">
                    <x-secondary-button wire:click="$set('showAnnulForm', false)">Cancelar</x-secondary-button>
                    <x-danger-button wire:click="annul">Confirmar anulación</x-danger-button>
                </div>
            </div>
        </div>
    @endif
</x-page>
