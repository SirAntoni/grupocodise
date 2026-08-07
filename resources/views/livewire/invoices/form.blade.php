<x-page :title="$invoice ? 'Factura — borrador #'.$invoice->id : 'Nueva factura'">
    @unless ($invoice)
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 space-y-5">
            <p class="text-sm text-slate-600">
                Elige la empresa y marca las guías que quieres facturar: se consolidan en un solo borrador.
            </p>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label value="Empresa" />
                    <select wire:model.live="clientId"
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">Seleccione…</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->business_name }} — {{ $client->ruc }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('clientId')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Vendedor" />
                    <select wire:model="sellerId"
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">Sin vendedor</option>
                        @foreach ($sellers as $seller)
                            <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('sellerId')" class="mt-1" />
                </div>
            </div>

            @if ($clientId)
                @include('livewire.invoices.partials.selector-guias', ['titulo' => 'Guías por facturar'])

                <div class="flex justify-end">
                    <x-primary-button wire:click="createDraft">Crear borrador</x-primary-button>
                </div>
            @endif
        </div>
    @else
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 space-y-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Cliente</div>
                    <div class="mt-0.5 font-semibold text-slate-900">{{ $invoice->client->business_name }}</div>
                    <div class="text-sm text-slate-500 font-mono">{{ $invoice->client->ruc }}</div>
                </div>
                <div class="flex items-start gap-4">
                    <div class="w-52">
                        <x-input-label value="Vendedor" />
                        <select wire:model="sellerId"
                                class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">Sin vendedor</option>
                            @foreach ($sellers as $seller)
                                <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('sellerId')" class="mt-1" />
                    </div>
                    <span class="mt-6 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $invoice->status->badgeColor() }}">
                        {{ $invoice->status->label() }}
                    </span>
                </div>
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
            </div>

            <div class="border-t border-slate-100 pt-5">
                @include('livewire.invoices.partials.selector-guias', ['titulo' => 'Agregar más guías de esta empresa'])

                @if ($availableGuides->isNotEmpty())
                    <div class="mt-3 flex justify-end">
                        <x-secondary-button wire:click="addSelectedGuides">Agregar las marcadas</x-secondary-button>
                    </div>
                @endif
            </div>
        </div>

        @php
            $tasaIgv = (float) config('facturacion.igv_rate');
            $lineas = $invoice->items
                ->where('type', \App\Enums\InvoiceItemType::Product)
                ->map(fn ($item) => ['id' => $item->id, 'cantidad' => (float) $item->quantity])
                ->values();
        @endphp

        {{-- Los importes se recalculan en el navegador con la misma aritmética
             del servidor (redondeo por línea), para que el número que ves al
             escribir el precio sea exactamente el que se guarda. --}}
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200"
             x-data="{
                 lineas: @js($lineas),
                 tasa: {{ $tasaIgv }},
                 dosDecimales(n) { return Math.round((n + Number.EPSILON) * 100) / 100 },
                 valor(id) { return Number($wire.prices[id]) || 0 },
                 subtotal(id, cantidad) { return this.dosDecimales(this.valor(id) * cantidad) },
                 igvLinea(id, cantidad) { return this.dosDecimales(this.subtotal(id, cantidad) * this.tasa) },
                 totalLinea(id, cantidad) { return this.dosDecimales(this.subtotal(id, cantidad) + this.igvLinea(id, cantidad)) },
                 get zona() { return $wire.hasRemoteZone ? this.dosDecimales(Number($wire.remoteZoneAmount) || 0) : 0 },
                 get gravado() { return this.dosDecimales(this.lineas.reduce((s, l) => s + this.subtotal(l.id, l.cantidad), 0) + this.zona) },
                 get igv() { return this.dosDecimales(this.lineas.reduce((s, l) => s + this.igvLinea(l.id, l.cantidad), 0) + this.dosDecimales(this.zona * this.tasa)) },
                 get total() { return this.dosDecimales(this.gravado + this.igv) },
                 soles(n) { return n.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
             }">
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
                            <td class="px-4 py-3 text-right tabular-nums"
                                x-text="soles(subtotal({{ $item->id }}, {{ (float) $item->quantity }}))">{{ number_format((float) $item->subtotal, 2) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums"
                                x-text="soles(igvLinea({{ $item->id }}, {{ (float) $item->quantity }}))">{{ number_format((float) $item->igv, 2) }}</td>
                            <td class="px-4 py-3 text-right font-medium tabular-nums"
                                x-text="soles(totalLinea({{ $item->id }}, {{ (float) $item->quantity }}))">{{ number_format((float) $item->total, 2) }}</td>
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
                    <div class="flex justify-between"><span class="text-slate-500">Op. gravadas</span><span class="tabular-nums" x-text="'S/ ' + soles(gravado)">S/ {{ number_format((float) $invoice->taxable_amount, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">IGV ({{ rtrim(rtrim(number_format($tasaIgv * 100, 1), '0'), '.') }}%)</span><span class="tabular-nums" x-text="'S/ ' + soles(igv)">S/ {{ number_format((float) $invoice->igv, 2) }}</span></div>
                    <div class="flex justify-between font-semibold text-base text-slate-900 border-t border-slate-200 pt-1.5"><span>Total</span><span class="tabular-nums" x-text="'S/ ' + soles(total)">S/ {{ number_format((float) $invoice->total, 2) }}</span></div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('facturas.ver', ['invoice' => $invoice->id]) }}" wire:navigate>
                <x-secondary-button>Cancelar</x-secondary-button>
            </a>
            <x-secondary-button wire:click="save">Guardar cambios</x-secondary-button>
            @can('issue', $invoice)
                <x-primary-button @click="$store.confirm.open('Al emitir se asignará numeración correlativa y la factura se enviará a SUNAT.', () => $wire.issue(), { title: 'Emitir factura', confirmText: 'Sí, emitir' })">
                    Emitir factura
                </x-primary-button>
            @endcan
        </div>
    @endunless
</x-page>
