<x-page :title="$requirement ? 'Editar requerimiento '.$requirement->code : 'Nuevo requerimiento'">
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 space-y-4">
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <x-input-label value="Cliente" />
                <select wire:model="client_id"
                        class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm">
                    <option value="">Seleccione…</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->business_name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('client_id')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Jefe de cuadrilla" />
                <x-text-input type="text" class="mt-1 block w-full" wire:model="crew_chief" />
                <x-input-error :messages="$errors->get('crew_chief')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Distrito" />
                <x-text-input type="text" class="mt-1 block w-full" wire:model="district" />
                <x-input-error :messages="$errors->get('district')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Fecha requerida" />
                <x-text-input type="date" class="mt-1 block w-full" wire:model="required_date" />
                <x-input-error :messages="$errors->get('required_date')" class="mt-1" />
            </div>
            <div class="md:col-span-2">
                <x-input-label value="Dirección de entrega (opcional, precarga la guía)" />
                <x-text-input type="text" class="mt-1 block w-full" wire:model="delivery_address" />
                <x-input-error :messages="$errors->get('delivery_address')" class="mt-1" />
            </div>
            <div class="md:col-span-2">
                <x-input-label value="Observaciones" />
                <textarea wire:model="notes" rows="2"
                          class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm"></textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-1" />
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="font-semibold text-slate-800">Materiales</h3>
            <x-secondary-button wire:click="addItem">Agregar ítem</x-secondary-button>
        </div>
        <x-input-error :messages="$errors->get('items')" class="mt-1" />

        <div class="space-y-2">
            @foreach ($items as $index => $item)
                <div class="flex flex-wrap items-start gap-3" wire:key="item-{{ $item['key'] }}">
                    <div class="flex-1 min-w-64">
                        <select wire:model="items.{{ $index }}.product_id"
                                class="block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm">
                            <option value="">Producto…</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">
                                    {{ $product->code }} — {{ $product->name }} ({{ \App\Support\SunatCatalogs::unitLabel($product->unit_code) }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('items.'.$index.'.product_id')" class="mt-1" />
                    </div>
                    <div class="w-32">
                        <x-text-input type="number" step="0.01" min="0" placeholder="Cantidad"
                                      class="block w-full" wire:model="items.{{ $index }}.quantity" />
                        <x-input-error :messages="$errors->get('items.'.$index.'.quantity')" class="mt-1" />
                    </div>
                    <button type="button" wire:click="removeItem({{ $index }})"
                            class="mt-2 text-red-600 hover:underline text-sm">Quitar</button>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex justify-end gap-2">
        <a href="{{ route('requerimientos.index') }}" wire:navigate>
            <x-secondary-button>Cancelar</x-secondary-button>
        </a>
        <x-primary-button wire:click="save">Guardar requerimiento</x-primary-button>
    </div>
</x-page>
