<x-page :title="'Factura '.($invoice->full_number ?? 'en borrador #'.$invoice->id)">
    <x-slot name="actions">
        @can('update', $invoice)
            <a href="{{ route('facturas.editar', ['invoice' => $invoice->id]) }}" wire:navigate>
                <x-secondary-button>Editar</x-secondary-button>
            </a>
        @endcan
        @can('delete', $invoice)
            <x-danger-button @click="$store.confirm.open('¿Eliminar este borrador de factura? Las guías quedarán libres para refacturar.', () => $wire.deleteDraft(), { danger: true, confirmText: 'Sí, eliminar' })">
                Eliminar borrador
            </x-danger-button>
        @endcan
        @if ($invoice->full_number)
            <a href="{{ route('facturas.pdf', ['invoice' => $invoice->id]) }}" target="_blank">
                <x-secondary-button>PDF</x-secondary-button>
            </a>
        @endif
        @can('resend', $invoice)
            <x-secondary-button wire:click="resend">Reenviar a SUNAT</x-secondary-button>
        @endcan
        @if ($invoice->electronicDocument && ! $invoice->electronicDocument->sunat_status->isAccepted())
            @can('invoices.manage')
                <x-secondary-button wire:click="processNow">Procesar envío ahora</x-secondary-button>
            @endcan
        @endif
        @can('annul', $invoice)
            <x-danger-button wire:click="$set('showAnnulForm', true)">Anular con NC</x-danger-button>
        @endcan
    </x-slot>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 grid sm:grid-cols-2 md:grid-cols-3 gap-4 text-sm">
        <div>
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Cliente</div>
            <div class="mt-0.5 font-medium text-slate-900">{{ $invoice->client->business_name }}</div>
            <div class="text-slate-500 font-mono">{{ $invoice->client->ruc }}</div>
        </div>
        <div>
            <div class="mb-1 text-xs font-medium uppercase tracking-wide text-slate-500">Estado</div>
            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $invoice->status->badgeColor() }}">{{ $invoice->status->label() }}</span>
            @if ($invoice->electronicDocument)
                <div class="mt-1 text-slate-500">SUNAT:
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $invoice->electronicDocument->sunat_status->badgeColor() }}">
                        {{ $invoice->electronicDocument->sunat_status->label() }}
                    </span>
                </div>
                @if ($invoice->electronicDocument->error_message)
                    <div class="mt-1 text-xs text-red-600">
                        {{ $invoice->electronicDocument->error_code }}: {{ $invoice->electronicDocument->error_message }}
                    </div>
                @endif
            @endif
        </div>
        <div>
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Emisión / vencimiento</div>
            <div class="mt-0.5 font-medium text-slate-900">
                {{ $invoice->issue_date?->format('d/m/Y') ?? '—' }} · vence {{ $invoice->due_date?->format('d/m/Y') ?? '—' }}
            </div>
            <div class="text-slate-500">Crédito a {{ config('facturacion.payment_due_days') }} días</div>
        </div>
        <div>
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Guías consolidadas</div>
            <div class="flex flex-wrap gap-2 mt-1">
                @foreach ($invoice->dispatchGuides as $guide)
                    <a href="{{ route('guias.ver', $guide) }}" wire:navigate
                       class="font-mono text-brand-600 hover:text-brand-700 hover:underline text-sm">{{ $guide->full_number }}</a>
                @endforeach
            </div>
        </div>
        <div>
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Orden de compra</div>
            <div class="mt-0.5 font-medium text-slate-900">{{ $invoice->purchaseOrder?->number ?? '—' }}</div>
        </div>
        <div>
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Registrada por</div>
            <div class="mt-0.5 font-medium text-slate-900">{{ $invoice->createdBy?->name }} · {{ $invoice->created_at->format('d/m/Y H:i') }}</div>
            @if ($invoice->issuedBy)
                <div class="text-slate-500">Emitida por {{ $invoice->issuedBy->name }}</div>
            @endif
        </div>
        @if ($invoice->receivable)
            <div class="sm:col-span-2 md:col-span-3 rounded-lg border border-slate-200 bg-slate-50 p-3 flex flex-wrap gap-x-6 gap-y-2 items-center">
                <div>
                    <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Cuenta por cobrar</span>
                    <span class="ms-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $invoice->receivable->traffic_light->badgeColor() }}">
                        {{ $invoice->receivable->traffic_light->label() }}
                    </span>
                    <span class="ms-1 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $invoice->receivable->status->badgeColor() }}">
                        {{ $invoice->receivable->status->label() }}
                    </span>
                </div>
                <div class="text-sm">Pagado: <strong class="tabular-nums">S/ {{ number_format((float) $invoice->receivable->paid_amount, 2) }}</strong></div>
                <div class="text-sm">Saldo: <strong class="tabular-nums">S/ {{ number_format((float) $invoice->receivable->balance, 2) }}</strong></div>
                <a href="{{ route('cobranzas.index') }}" wire:navigate class="font-medium text-brand-600 hover:text-brand-700 hover:underline text-sm">Ir a cobranzas</a>
            </div>
        @endif
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Descripción</th>
                    <th class="px-4 py-3">Unidad</th>
                    <th class="px-4 py-3 text-right">Cantidad</th>
                    <th class="px-4 py-3 text-right">Valor unit.</th>
                    <th class="px-4 py-3 text-right">Subtotal</th>
                    <th class="px-4 py-3 text-right">IGV</th>
                    <th class="px-4 py-3 text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($invoice->items as $item)
                    <tr wire:key="show-item-{{ $item->id }}">
                        <td class="px-4 py-3">
                            {{ $item->description }}
                            @if ($item->type === \App\Enums\InvoiceItemType::RemoteZone)
                                <span class="ms-1 inline-flex items-center rounded bg-amber-100 px-1.5 py-0.5 text-xs font-medium text-amber-800">adicional</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $item->unit_code }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $item->quantity, 2) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $item->unit_value, 4) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $item->subtotal, 2) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $item->igv, 2) }}</td>
                        <td class="px-4 py-3 text-right font-medium">{{ number_format((float) $item->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-slate-50 text-sm">
                <tr>
                    <td colspan="5"></td>
                    <td class="px-4 py-2 text-right text-slate-500">Op. gravadas</td>
                    <td class="px-4 py-2 text-right">S/ {{ number_format((float) $invoice->taxable_amount, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="5"></td>
                    <td class="px-4 py-2 text-right text-slate-500">IGV</td>
                    <td class="px-4 py-2 text-right">S/ {{ number_format((float) $invoice->igv, 2) }}</td>
                </tr>
                <tr class="font-semibold">
                    <td colspan="5"></td>
                    <td class="px-4 py-2 text-right">Total</td>
                    <td class="px-4 py-2 text-right">S/ {{ number_format((float) $invoice->total, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if ($invoice->creditNotes->isNotEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <h3 class="mb-3 text-sm font-semibold text-slate-900">Notas de crédito</h3>
            @foreach ($invoice->creditNotes as $creditNote)
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 py-2 text-sm last:border-b-0"
                     wire:key="cn-{{ $creditNote->id }}">
                    <span class="font-mono font-medium text-slate-900">{{ $creditNote->full_number }}</span>
                    <span>{{ $creditNote->issue_date->format('d/m/Y') }}</span>
                    <span class="text-slate-500">{{ $creditNote->motive_description }}</span>
                    <span class="tabular-nums">S/ {{ number_format((float) $creditNote->total, 2) }}</span>
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $creditNote->status->badgeColor() }}">
                        {{ $creditNote->status->label() }}
                    </span>
                    @if ($creditNote->electronicDocument)
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $creditNote->electronicDocument->sunat_status->badgeColor() }}">
                            SUNAT: {{ $creditNote->electronicDocument->sunat_status->label() }}
                        </span>
                    @endif
                    <a href="{{ route('notas-credito.pdf', ['creditNote' => $creditNote->id]) }}" target="_blank"
                       class="font-medium text-brand-600 hover:text-brand-700 hover:underline">PDF</a>
                </div>
            @endforeach
        </div>
    @endif

    @if ($showAnnulForm)
        <div class="fixed inset-0 z-[80] flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" wire:click="$set('showAnnulForm', false)"></div>
            <div class="relative w-full max-w-lg space-y-4 rounded-xl bg-white p-4 shadow-xl sm:p-6">
                <h3 class="text-lg font-semibold text-slate-900">Anular factura {{ $invoice->full_number }}</h3>
                <p class="text-sm text-slate-500">
                    Se emitirá una <strong>nota de crédito por el total</strong> (S/ {{ number_format((float) $invoice->total, 2) }})
                    ante SUNAT y las guías asociadas quedarán libres para refacturar.
                </p>
                <div>
                    <x-input-label value="Motivo de anulación" />
                    <textarea wire:model="annulment_reason" rows="3"
                              class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm"></textarea>
                    <x-input-error :messages="$errors->get('annulment_reason')" class="mt-1" />
                </div>
                <div class="flex justify-end gap-2">
                    <x-secondary-button wire:click="$set('showAnnulForm', false)">Cancelar</x-secondary-button>
                    <x-danger-button wire:click="annul">Emitir nota de crédito</x-danger-button>
                </div>
            </div>
        </div>
    @endif
</x-page>
