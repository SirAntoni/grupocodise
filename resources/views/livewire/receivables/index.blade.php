<x-page title="Tablero de cobranza">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach (\App\Enums\TrafficLight::cases() as $light)
            @php $row = $openTotals[$light->value] ?? null; @endphp
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200 p-5 flex items-center gap-4">
                <span class="h-4 w-4 rounded-full {{ $light === \App\Enums\TrafficLight::Green ? 'bg-green-500' : ($light === \App\Enums\TrafficLight::Yellow ? 'bg-amber-400' : 'bg-red-500') }}"></span>
                <div>
                    <div class="text-sm text-slate-500">{{ $light->label() }}</div>
                    <div class="text-xl font-semibold text-slate-900">
                        {{ $row->cuenta ?? 0 }} <span class="text-sm font-normal text-slate-500">cuentas</span>
                    </div>
                    <div class="text-sm text-slate-500">S/ {{ number_format((float) ($row->saldo ?? 0), 2) }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200 p-4 grid gap-3 md:grid-cols-3">
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
            <x-input-label value="Semáforo" />
            <select wire:model.live="lightFilter"
                    class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm">
                <option value="">Todos</option>
                @foreach ($lights as $light)
                    <option value="{{ $light->value }}">{{ $light->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label value="Estado" />
            <select wire:model.live="statusFilter"
                    class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm">
                <option value="abiertas">Abiertas (pendiente + parcial)</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
                <option value="">Todas</option>
            </select>
        </div>
    </div>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200 overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-medium text-slate-500 uppercase">
                <tr>
                    <th class="px-4 py-3">Factura</th>
                    <th class="px-4 py-3">Cliente</th>
                    <th class="px-4 py-3">Emisión</th>
                    <th class="px-4 py-3">Vence</th>
                    <th class="px-4 py-3 text-right">Monto</th>
                    <th class="px-4 py-3 text-right">Pagado</th>
                    <th class="px-4 py-3 text-right">Saldo</th>
                    <th class="px-4 py-3">Semáforo</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($receivables as $receivable)
                    <tr wire:key="cxc-{{ $receivable->id }}">
                        <td class="px-4 py-3 font-mono">
                            <a href="{{ route('facturas.ver', $receivable->invoice) }}" wire:navigate class="text-brand-600 hover:underline">
                                {{ $receivable->invoice->full_number }}
                            </a>
                        </td>
                        <td class="px-4 py-3">{{ $receivable->invoice->client->business_name }}</td>
                        <td class="px-4 py-3">{{ $receivable->invoice->issue_date?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $receivable->due_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-right">S/ {{ number_format((float) $receivable->amount, 2) }}</td>
                        <td class="px-4 py-3 text-right">S/ {{ number_format((float) $receivable->paid_amount, 2) }}</td>
                        <td class="px-4 py-3 text-right font-medium">S/ {{ number_format((float) $receivable->balance, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs {{ $receivable->traffic_light->badgeColor() }}">
                                {{ $receivable->traffic_light->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs {{ $receivable->status->badgeColor() }}">
                                {{ $receivable->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @can('receivables.manage')
                                @if ($receivable->isOpen())
                                    <button wire:click="openPaymentForm({{ $receivable->id }})" class="text-green-700 hover:underline">
                                        Registrar pago
                                    </button>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="px-4 py-8 text-center text-slate-400">Sin cuentas por cobrar que mostrar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $receivables->links() }}</div>

    @if ($showPaymentForm)
        <div class="fixed inset-0 z-[80] flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" wire:click="$set('showPaymentForm', false)"></div>
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6 space-y-4">
                <h3 class="text-lg font-semibold text-slate-800">Registrar pago</h3>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label value="Fecha de pago" />
                        <x-text-input type="date" class="mt-1 block w-full" wire:model="payment_date" />
                        <x-input-error :messages="$errors->get('payment_date')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label value="Monto (S/)" />
                        <x-text-input type="number" step="0.01" min="0" class="mt-1 block w-full" wire:model="amount" />
                        <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                    </div>
                </div>
                <div>
                    <x-input-label value="Medio de pago" />
                    <select wire:model="method"
                            class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm">
                        @foreach ($methods as $method)
                            <option value="{{ $method->value }}">{{ $method->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('method')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="N° de operación / referencia" />
                    <x-text-input type="text" class="mt-1 block w-full" wire:model="reference" />
                    <x-input-error :messages="$errors->get('reference')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Observación" />
                    <x-text-input type="text" class="mt-1 block w-full" wire:model="notes" />
                    <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <x-secondary-button wire:click="$set('showPaymentForm', false)">Cancelar</x-secondary-button>
                    <x-primary-button wire:click="savePayment">Registrar pago</x-primary-button>
                </div>
            </div>
        </div>
    @endif
</x-page>
