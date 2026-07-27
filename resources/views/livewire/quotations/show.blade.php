<x-page :title="'Cotización '.$quotation->code">
    <x-slot name="actions">
        @can('quotations.manage')
            @if ($quotation->isEditable())
                <a href="{{ route('cotizaciones.editar', $quotation) }}" wire:navigate>
                    <x-secondary-button>Editar</x-secondary-button>
                </a>
                <x-primary-button wire:click="accept">Marcar aceptada</x-primary-button>
                <x-danger-button wire:click="reject" wire:confirm="¿Marcar la cotización como rechazada?">Rechazar</x-danger-button>
            @endif
        @endcan
        @can('purchase-orders.manage')
            @if ($quotation->status === \App\Enums\QuotationStatus::Accepted && ! $quotation->purchaseOrder)
                <x-primary-button wire:click="generatePurchaseOrder">Generar orden de compra</x-primary-button>
            @endif
        @endcan
    </x-slot>

    <div class="bg-white shadow-sm sm:rounded-lg p-6 grid md:grid-cols-3 gap-4 text-sm">
        <div>
            <div class="text-gray-500">Cliente</div>
            <div class="font-medium">{{ $quotation->client->business_name }}</div>
            <div class="text-gray-500 font-mono">{{ $quotation->client->ruc }}</div>
        </div>
        <div>
            <div class="text-gray-500">Estado</div>
            <span class="px-2 py-0.5 rounded-full text-xs {{ $quotation->status->badgeColor() }}">
                {{ $quotation->status->label() }}
            </span>
            @if ($quotation->statusChangedBy)
                <div class="text-gray-500 mt-1">
                    por {{ $quotation->statusChangedBy->name }} · {{ $quotation->status_changed_at?->format('d/m/Y H:i') }}
                </div>
            @endif
        </div>
        <div>
            <div class="text-gray-500">Vigencia</div>
            <div class="font-medium">{{ $quotation->issue_date->format('d/m/Y') }} — {{ $quotation->valid_until->format('d/m/Y') }}</div>
        </div>
        @if ($quotation->purchaseOrder)
            <div>
                <div class="text-gray-500">Orden de compra generada</div>
                <div class="font-mono font-medium">{{ $quotation->purchaseOrder->number }}</div>
            </div>
        @endif
        @if ($quotation->notes)
            <div class="md:col-span-3">
                <div class="text-gray-500">Observaciones</div>
                <div>{{ $quotation->notes }}</div>
            </div>
        @endif
    </div>

    <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3">Descripción</th>
                    <th class="px-4 py-3">Unidad</th>
                    <th class="px-4 py-3 text-right">Cantidad</th>
                    <th class="px-4 py-3 text-right">Precio unit.</th>
                    <th class="px-4 py-3 text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($quotation->items as $item)
                    <tr wire:key="qshow-{{ $item->id }}">
                        <td class="px-4 py-3">
                            {{ $item->description }}
                            @if ($item->product)
                                <span class="text-gray-400 text-xs">({{ $item->product->code }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $item->unit_code }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $item->quantity, 2) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $item->unit_value, 4) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50">
                <tr>
                    <td colspan="3"></td>
                    <td class="px-4 py-2 text-right text-gray-500">Subtotal</td>
                    <td class="px-4 py-2 text-right">S/ {{ number_format((float) $quotation->taxable_amount, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="3"></td>
                    <td class="px-4 py-2 text-right text-gray-500">IGV (18%)</td>
                    <td class="px-4 py-2 text-right">S/ {{ number_format((float) $quotation->igv, 2) }}</td>
                </tr>
                <tr class="font-semibold">
                    <td colspan="3"></td>
                    <td class="px-4 py-2 text-right">Total</td>
                    <td class="px-4 py-2 text-right">S/ {{ number_format((float) $quotation->total, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</x-page>
