<x-page title="Historial de pagos">
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200 p-4 grid gap-3 md:grid-cols-4">
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
            <x-input-label value="Factura" />
            <select wire:model.live="invoiceFilter"
                    class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm">
                <option value="">Todas</option>
                @foreach ($invoices as $invoice)
                    <option value="{{ $invoice->id }}">{{ $invoice->full_number }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label value="Desde" />
            <x-text-input type="date" class="mt-1 block w-full" wire:model.live="from" />
        </div>
        <div>
            <x-input-label value="Hasta" />
            <x-text-input type="date" class="mt-1 block w-full" wire:model.live="until" />
        </div>
    </div>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200 overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-medium text-slate-500 uppercase">
                <tr>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Factura</th>
                    <th class="px-4 py-3">Cliente</th>
                    <th class="px-4 py-3 text-right">Monto</th>
                    <th class="px-4 py-3">Medio</th>
                    <th class="px-4 py-3">Referencia</th>
                    <th class="px-4 py-3">Registrado por</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($payments as $payment)
                    <tr wire:key="pay-{{ $payment->id }}">
                        <td class="px-4 py-3">{{ $payment->payment_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 font-mono">
                            <a href="{{ route('facturas.ver', $payment->receivable->invoice) }}" wire:navigate class="text-brand-600 hover:underline">
                                {{ $payment->receivable->invoice->full_number }}
                            </a>
                        </td>
                        <td class="px-4 py-3">{{ $payment->receivable->invoice->client->business_name }}</td>
                        <td class="px-4 py-3 text-right">S/ {{ number_format((float) $payment->amount, 2) }}</td>
                        <td class="px-4 py-3">{{ $payment->method->label() }}</td>
                        <td class="px-4 py-3">{{ $payment->reference ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $payment->createdBy?->name }}</td>
                        <td class="px-4 py-3 text-right">
                            @can('receivables.manage')
                                <button wire:click="openDeleteForm({{ $payment->id }})" class="text-red-600 hover:underline">Anular</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-slate-400">Sin pagos que mostrar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $payments->links() }}</div>

    @if ($showDeleteForm)
        <div class="fixed inset-0 z-[80] flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" wire:click="$set('showDeleteForm', false)"></div>
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6 space-y-4">
                <h3 class="text-lg font-semibold text-slate-800">Anular pago</h3>
                <p class="text-sm text-slate-500">El saldo y el estado de la cuenta por cobrar se recalcularán.</p>
                <div>
                    <x-input-label value="Motivo de anulación" />
                    <textarea wire:model="deletion_reason" rows="3"
                              class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm"></textarea>
                    <x-input-error :messages="$errors->get('deletion_reason')" class="mt-1" />
                </div>
                <div class="flex justify-end gap-2">
                    <x-secondary-button wire:click="$set('showDeleteForm', false)">Cancelar</x-secondary-button>
                    <x-danger-button wire:click="confirmDelete">Anular pago</x-danger-button>
                </div>
            </div>
        </div>
    @endif
</x-page>
