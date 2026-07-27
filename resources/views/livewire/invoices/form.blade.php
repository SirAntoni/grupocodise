<x-page :title="$invoice ? 'Factura — borrador #'.$invoice->id : 'Nueva factura'">
    @unless ($invoice)
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 space-y-4 max-w-xl">
            <p class="text-sm text-slate-600">
                Digita el número de una guía <strong>emitida</strong> (por ejemplo <span class="font-mono">T001-00000001</span>).
                La factura se creará en borrador consolidando sus ítems; luego podrás agregar más guías del mismo cliente.
            </p>
            <div>
                <x-input-label value="Número de guía" />
                <x-text-input type="text" class="mt-1 block w-full font-mono" placeholder="T001-00000001"
                              wire:model="guideNumber" wire:keydown.enter="createDraft" />
                <x-input-error :messages="$errors->get('guideNumber')" class="mt-1" />
            </div>
            <div class="flex justify-end">
                <x-primary-button wire:click="createDraft">Crear borrador</x-primary-button>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 space-y-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Cliente</div>
                    <div class="mt-0.5 font-semibold text-slate-900">{{ $invoice->client->business_name }}</div>
                    <div class="text-sm text-slate-500 font-mono">{{ $invoice->client->ruc }}</div>
                </div>
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $invoice->status->badgeColor() }}">
                    {{ $invoice->status->label() }}
                </span>
            </div>

            <div>
                <h3 class="mb-2 text-sm font-semibold text-slate-900">Guías consolidadas</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach ($invoice->dispatchGuides as $guide)
                        <span class="inline-flex items-center gap-2 rounded-full border border-brand-100 bg-brand-50 px-3 py-1 text-sm font-mono text-brand-800"
                              wire:key="chip-{{ $guide->id }}">
                            {{ $guide->full_number }}
                            <button wire:click="removeGuide({{ $guide->id }})" class="text-brand-400 transition hover:text-red-600" title="Quitar">✕</button>
                        </span>
                    @endforeach
                </div>
                <div class="mt-3 flex gap-2 max-w-md">
                    <div class="flex-1">
                        <x-text-input type="text" class="block w-full font-mono" placeholder="Agregar guía: T001-…"
                                      wire:model="guideNumber" wire:keydown.enter="addGuide" />
                        <x-input-error :messages="$errors->get('guideNumber')" class="mt-1" />
                    </div>
                    <x-secondary-button wire:click="addGuide">Agregar</x-secondary-button>
                </div>
            </div>
        </div>

        <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Descripción</th>
                        <th class="px-4 py-3">Unidad</th>
                        <th class="px-4 py-3 text-right">Cantidad</th>
                        <th class="px-4 py-3 text-right w-40">Valor unit. (sin IGV)</th>
                        <th class="px-4 py-3 text-right">Subtotal</th>
                        <th class="px-4 py-3 text-right">IGV</th>
                        <th class="px-4 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($invoice->items->where('type', \App\Enums\InvoiceItemType::Product) as $item)
                        <tr wire:key="inv-item-{{ $item->id }}">
                            <td class="px-4 py-3">{{ $item->description }}</td>
                            <td class="px-4 py-3">{{ $item->unit_code }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $item->quantity, 2) }}</td>
                            <td class="px-4 py-2 text-right">
                                <x-text-input type="number" step="0.0001" min="0" class="block w-full text-right"
                                              wire:model="prices.{{ $item->id }}" />
                                <x-input-error :messages="$errors->get('prices.'.$item->id)" class="mt-1" />
                            </td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $item->subtotal, 2) }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $item->igv, 2) }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ number_format((float) $item->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>

            <div class="p-4 border-t border-slate-100 flex flex-wrap items-center gap-4">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model.live="hasRemoteZone"
                           class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    Agregar línea de <strong>zona lejana</strong>
                </label>
                @if ($hasRemoteZone)
                    <div class="w-48">
                        <x-text-input type="number" step="0.01" min="0" placeholder="Monto sin IGV"
                                      class="block w-full text-right" wire:model="remoteZoneAmount" />
                        <x-input-error :messages="$errors->get('remoteZoneAmount')" class="mt-1" />
                    </div>
                @endif
            </div>

            <div class="p-4 border-t border-slate-100 flex justify-end">
                <div class="w-full max-w-72 space-y-1.5 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">Op. gravadas</span><span class="tabular-nums">S/ {{ number_format((float) $invoice->taxable_amount, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">IGV (18%)</span><span class="tabular-nums">S/ {{ number_format((float) $invoice->igv, 2) }}</span></div>
                    <div class="flex justify-between font-semibold text-base text-slate-900 border-t border-slate-200 pt-1.5"><span>Total</span><span class="tabular-nums">S/ {{ number_format((float) $invoice->total, 2) }}</span></div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('facturas.ver', ['invoice' => $invoice->id]) }}" wire:navigate>
                <x-secondary-button>Cancelar</x-secondary-button>
            </a>
            <x-secondary-button wire:click="save">Guardar cambios</x-secondary-button>
            @can('issue', $invoice)
                <x-primary-button wire:click="issue"
                                  wire:confirm="Al emitir se asignará numeración y se enviará a SUNAT. ¿Continuar?">
                    Emitir factura
                </x-primary-button>
            @endcan
        </div>
    @endunless
</x-page>
