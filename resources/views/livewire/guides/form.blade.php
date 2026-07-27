<x-page :title="'Guía de despacho — borrador'.($guide?->requirement ? ' · '.$guide->requirement->code : '')">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-6 space-y-4">
        <h3 class="text-base font-semibold text-slate-900">Datos del traslado</h3>
        <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-4">
            <div>
                <x-input-label value="Cliente" />
                <div class="mt-2 text-sm font-medium">{{ $guide->client->business_name }}</div>
                <div class="text-xs text-slate-500 font-mono">{{ $guide->client->ruc }}</div>
            </div>
            <div>
                <x-input-label value="Fecha de inicio de traslado" />
                <x-text-input type="date" class="mt-1 block w-full" wire:model="transfer_date" />
                <x-input-error :messages="$errors->get('transfer_date')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Orden de compra (opcional)" />
                <select wire:model="purchase_order_id"
                        class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm">
                    <option value="">— Sin OC —</option>
                    @foreach ($purchaseOrders as $po)
                        <option value="{{ $po->id }}">{{ $po->number }} ({{ $po->date->format('d/m/Y') }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('purchase_order_id')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Motivo de traslado" />
                <select wire:model="transfer_reason_code"
                        class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm">
                    @foreach ($transferReasons as $code => $label)
                        <option value="{{ $code }}">{{ $code }} — {{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('transfer_reason_code')" class="mt-1" />
            </div>
            <div class="md:col-span-2">
                <x-input-label value="Dirección de llegada" />
                <x-text-input type="text" class="mt-1 block w-full" wire:model="delivery_address" />
                <x-input-error :messages="$errors->get('delivery_address')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Ubigeo de llegada (opcional)" />
                <x-text-input type="text" maxlength="6" class="mt-1 block w-full" placeholder="150101" wire:model="delivery_ubigeo" />
                <x-input-error :messages="$errors->get('delivery_ubigeo')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Distrito" />
                <x-text-input type="text" class="mt-1 block w-full" wire:model="district" />
                <x-input-error :messages="$errors->get('district')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Jefe de cuadrilla" />
                <x-text-input type="text" class="mt-1 block w-full" wire:model="crew_chief" />
                <x-input-error :messages="$errors->get('crew_chief')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Peso bruto total (kg)" />
                <x-text-input type="number" step="0.01" min="0" class="mt-1 block w-full" wire:model="total_weight" />
                <x-input-error :messages="$errors->get('total_weight')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Número de bultos (opcional)" />
                <x-text-input type="number" min="1" class="mt-1 block w-full" wire:model="packages_count" />
                <x-input-error :messages="$errors->get('packages_count')" class="mt-1" />
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-6 space-y-4">
        <h3 class="text-base font-semibold text-slate-900">Transporte</h3>
        <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-4">
            <div>
                <x-input-label value="Modalidad" />
                <select wire:model.live="transport_mode"
                        class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm">
                    <option value="">Seleccione…</option>
                    <option value="publico">Transporte público (transportista)</option>
                    <option value="privado">Transporte privado (vehículo propio)</option>
                </select>
                <x-input-error :messages="$errors->get('transport_mode')" class="mt-1" />
            </div>

            @if ($transport_mode === 'publico')
                <div>
                    <x-input-label value="RUC del transportista" />
                    <div class="mt-1 flex gap-2">
                        <x-text-input type="text" maxlength="11" class="block w-full" wire:model="carrier_ruc"
                                      wire:keydown.enter="lookupCarrier" />
                        <x-secondary-button wire:click="lookupCarrier" wire:loading.attr="disabled" wire:target="lookupCarrier" title="Buscar en el padrón SUNAT">
                            <span wire:loading.remove wire:target="lookupCarrier">Buscar</span>
                            <span wire:loading wire:target="lookupCarrier">…</span>
                        </x-secondary-button>
                    </div>
                    <x-input-error :messages="$errors->get('carrier_ruc')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Razón social del transportista" />
                    <x-text-input type="text" class="mt-1 block w-full" wire:model="carrier_name" />
                    <x-input-error :messages="$errors->get('carrier_name')" class="mt-1" />
                </div>
            @elseif ($transport_mode === 'privado')
                <div>
                    <x-input-label value="Placa del vehículo" />
                    <x-text-input type="text" maxlength="10" class="mt-1 block w-full" wire:model="vehicle_plate" />
                    <x-input-error :messages="$errors->get('vehicle_plate')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Nombres del conductor" />
                    <x-text-input type="text" class="mt-1 block w-full" wire:model="driver_first_names" />
                    <x-input-error :messages="$errors->get('driver_first_names')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Apellidos del conductor" />
                    <x-text-input type="text" class="mt-1 block w-full" wire:model="driver_last_names" />
                    <x-input-error :messages="$errors->get('driver_last_names')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Tipo de documento" />
                    <select wire:model="driver_document_type"
                            class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm">
                        <option value="1">DNI</option>
                        <option value="4">Carnet de extranjería</option>
                        <option value="7">Pasaporte</option>
                    </select>
                    <x-input-error :messages="$errors->get('driver_document_type')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="N° de documento del conductor" />
                    <div class="mt-1 flex gap-2">
                        <x-text-input type="text" maxlength="15" class="block w-full" wire:model="driver_document"
                                      wire:keydown.enter="lookupDriver" />
                        <x-secondary-button wire:click="lookupDriver" wire:loading.attr="disabled" wire:target="lookupDriver" title="Buscar nombres por DNI">
                            <span wire:loading.remove wire:target="lookupDriver">Buscar</span>
                            <span wire:loading wire:target="lookupDriver">…</span>
                        </x-secondary-button>
                    </div>
                    <x-input-error :messages="$errors->get('driver_document')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Licencia de conducir" />
                    <x-text-input type="text" maxlength="15" class="mt-1 block w-full" wire:model="driver_license" />
                    <x-input-error :messages="$errors->get('driver_license')" class="mt-1" />
                </div>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-6 space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-base font-semibold text-slate-900">Materiales</h3>
            <x-secondary-button wire:click="addItem">Agregar ítem</x-secondary-button>
        </div>
        <x-input-error :messages="$errors->get('items')" class="mt-1" />

        <div class="hidden md:grid md:grid-cols-12 gap-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
            <div class="col-span-6">Producto</div>
            <div class="col-span-2 text-right">Solicitada</div>
            <div class="col-span-2 text-right">Despachada</div>
            <div class="col-span-2"></div>
        </div>

        <div class="space-y-2">
            @foreach ($items as $index => $item)
                <div class="grid grid-cols-2 md:grid-cols-12 gap-3 items-start rounded-lg bg-slate-50 p-3 md:bg-transparent md:p-0" wire:key="gitem-{{ $item['key'] }}">
                    <div class="col-span-2 md:col-span-6">
                        <select wire:model="items.{{ $index }}.product_id"
                                class="block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm">
                            <option value="">Producto…</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">
                                    {{ $product->code }} — {{ $product->name }} (stock: {{ number_format((float) $product->stock, 2) }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('items.'.$index.'.product_id')" class="mt-1" />
                    </div>
                    <div class="md:col-span-2">
                        <span class="md:hidden block mb-1 text-xs font-medium text-slate-500">Solicitada</span>
                        <x-text-input type="number" step="0.01" min="0" class="block w-full text-right"
                                      wire:model="items.{{ $index }}.quantity_requested" />
                        <x-input-error :messages="$errors->get('items.'.$index.'.quantity_requested')" class="mt-1" />
                    </div>
                    <div class="md:col-span-2">
                        <span class="md:hidden block mb-1 text-xs font-medium text-slate-500">Despachada</span>
                        <x-text-input type="number" step="0.01" min="0" class="block w-full text-right"
                                      wire:model="items.{{ $index }}.quantity_dispatched" />
                        <x-input-error :messages="$errors->get('items.'.$index.'.quantity_dispatched')" class="mt-1" />
                    </div>
                    <div class="col-span-2 md:col-span-2 md:pt-2">
                        <button type="button" wire:click="removeItem({{ $index }})"
                                class="text-sm font-medium text-red-600 hover:text-red-700 hover:underline">Quitar</button>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="text-xs text-slate-500">La cantidad despachada es la que descuenta stock al emitir; puede diferir de la solicitada.</p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-6">
        <x-input-label value="Observaciones" />
        <textarea wire:model="notes" rows="2"
                  class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm"></textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-1" />
    </div>

    <div class="flex flex-wrap justify-end gap-2">
        <a href="{{ route('guias.ver', ['dispatchGuide' => $guide->id]) }}" wire:navigate>
            <x-secondary-button>Cancelar</x-secondary-button>
        </a>
        <x-secondary-button wire:click="saveDraft">Guardar borrador</x-secondary-button>
        <x-primary-button @click="$store.confirm.open('Al emitir se asignará numeración correlativa y se descontará el stock despachado.', () => $wire.issue(), { title: 'Emitir guía', confirmText: 'Sí, emitir' })">
            Emitir guía
        </x-primary-button>
    </div>
</x-page>
