<x-page :title="$quotation ? 'Editar cotización '.$quotation->code : 'Nueva cotización'">
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200 p-6 space-y-4">
        <div class="grid md:grid-cols-3 gap-4">
            <div>
                <x-input-label value="Cliente" />
                <select wire:model="client_id"
                        class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-sm">
                    <option value="">Seleccione…</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->business_name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('client_id')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Fecha de emisión" />
                <x-text-input type="date" class="mt-1 block w-full" wire:model="issue_date" />
                <x-input-error :messages="$errors->get('issue_date')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Vigente hasta" />
                <x-text-input type="date" class="mt-1 block w-full" wire:model="valid_until" />
                <x-input-error :messages="$errors->get('valid_until')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Vendedor" />
                <select wire:model="seller_id"
                        class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-sm">
                    <option value="">Sin vendedor</option>
                    @foreach ($sellers as $seller)
                        <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('seller_id')" class="mt-1" />
            </div>
            <div class="md:col-span-2">
                <x-input-label value="Observaciones" />
                <textarea wire:model="notes" rows="2"
                          class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm"></textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-1" />
            </div>
        </div>
    </div>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200 p-6 space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">Ítems</h3>
            <x-secondary-button wire:click="addItem">Agregar ítem</x-secondary-button>
        </div>
        <x-input-error :messages="$errors->get('items')" class="mt-1" />

        <div class="hidden md:grid md:grid-cols-12 gap-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
            <div class="col-span-3">Producto (opcional)</div>
            <div class="col-span-3">Descripción</div>
            <div class="col-span-2">Unidad</div>
            <div class="col-span-1 text-right">Cantidad</div>
            <div class="col-span-2 text-right">Precio unit. (sin IGV)</div>
            <div class="col-span-1"></div>
        </div>

        <div class="space-y-2">
            @foreach ($items as $index => $item)
                <div class="grid md:grid-cols-12 gap-3 items-start" wire:key="qitem-{{ $item['key'] }}">
                    <div class="md:col-span-3">
                        <select wire:model.live="items.{{ $index }}.product_id"
                                class="block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-sm">
                            <option value="">— Libre —</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">{{ $product->code }} — {{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <x-text-input type="text" class="block w-full" wire:model="items.{{ $index }}.description" />
                        <x-input-error :messages="$errors->get('items.'.$index.'.description')" class="mt-1" />
                    </div>
                    <div class="md:col-span-2">
                        <select wire:model="items.{{ $index }}.unit_code"
                                class="block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-sm">
                            @foreach ($units as $unitCode => $label)
                                <option value="{{ $unitCode }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-1">
                        <x-text-input type="number" step="0.01" min="0" class="block w-full text-right"
                                      wire:model="items.{{ $index }}.quantity" />
                        <x-input-error :messages="$errors->get('items.'.$index.'.quantity')" class="mt-1" />
                    </div>
                    <div class="md:col-span-2">
                        <x-text-input type="number" step="0.0001" min="0" class="block w-full text-right"
                                      wire:model="items.{{ $index }}.unit_value" />
                        <x-input-error :messages="$errors->get('items.'.$index.'.unit_value')" class="mt-1" />
                    </div>
                    <div class="md:col-span-1 pt-2">
                        <button type="button" wire:click="removeItem({{ $index }})"
                                class="text-red-600 hover:underline text-sm">Quitar</button>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="text-xs text-slate-500">Los totales (subtotal, IGV 18 % y total) se calculan al guardar.</p>
    </div>

    <div class="flex flex-wrap justify-end gap-2">
        <a href="{{ route('cotizaciones.index') }}" wire:navigate>
            <x-secondary-button>Cancelar</x-secondary-button>
        </a>
        <x-primary-button wire:click="save">Guardar cotización</x-primary-button>
    </div>
</x-page>
