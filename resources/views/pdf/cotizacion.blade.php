<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        .header { width: 100%; margin-bottom: 10px; }
        .header td { vertical-align: top; }
        .doc-box { border: 2px solid #111; text-align: center; padding: 8px 14px; width: 220px; }
        .doc-box .title { font-size: 12px; font-weight: bold; }
        .doc-box .number { font-size: 13px; font-weight: bold; margin-top: 4px; }
        .company-name { font-size: 13px; font-weight: bold; }
        .section { border: 1px solid #444; border-radius: 3px; padding: 6px 8px; margin-bottom: 8px; }
        .label { color: #555; font-size: 8px; text-transform: uppercase; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.items th, table.items td { border: 1px solid #444; padding: 4px 6px; }
        table.items th { background: #eee; text-transform: uppercase; font-size: 8px; }
        .right { text-align: right; }
        .center { text-align: center; }
        table.totals { width: 260px; border-collapse: collapse; margin-left: auto; }
        table.totals td { padding: 3px 6px; }
        table.totals .total-row td { border-top: 1px solid #111; font-weight: bold; font-size: 11px; }
        .footnote { font-size: 8px; color: #555; margin-top: 6px; }
        .legend { border: 1px solid #444; border-radius: 3px; padding: 6px 8px; margin: 8px 0; font-style: italic; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                @include('pdf.partials.emisor')
            </td>
            <td align="right">
                <div class="doc-box">
                    <div class="title">COTIZACIÓN</div>
                    <div class="number">{{ $quotation->code }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section">
        <table width="100%">
            <tr>
                <td>
                    <span class="label">Señor(es):</span> <strong>{{ $quotation->client->business_name }}</strong><br>
                    <span class="label">RUC:</span> {{ $quotation->client->document_number }}<br>
                    <span class="label">Dirección:</span> {{ $quotation->client->address }}
                </td>
                <td width="220" style="vertical-align: top;">
                    <span class="label">Fecha:</span> {{ $quotation->issue_date->format('d/m/Y') }}<br>
                    <span class="label">Válida hasta:</span> {{ $quotation->valid_until->format('d/m/Y') }}<br>
                    <span class="label">Moneda:</span> SOLES
                    @if ($quotation->seller)
                        <br><span class="label">Vendedor:</span> {{ $quotation->seller->name }}
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 26px;">#</th>
                <th style="width: 65px;">Código</th>
                <th>Descripción</th>
                <th style="width: 45px;">Unidad</th>
                <th style="width: 60px;">Cantidad</th>
                <th style="width: 70px;">V. unitario</th>
                <th style="width: 70px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($quotation->items as $item)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $item->product?->code ?? '—' }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="center">{{ $item->unit_code }}</td>
                    <td class="right">{{ number_format((float) $item->quantity, 2) }}</td>
                    <td class="right">{{ number_format((float) $item->unit_value, 2) }}</td>
                    <td class="right">{{ number_format((float) $item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="legend">{{ \App\Support\NumeroALetras::legend((float) $quotation->total) }}</div>

    <table class="totals">
        <tr><td class="label">Op. gravadas:</td><td class="right">S/ {{ number_format((float) $quotation->taxable_amount, 2) }}</td></tr>
        <tr><td class="label">IGV (18%):</td><td class="right">S/ {{ number_format((float) $quotation->igv, 2) }}</td></tr>
        <tr class="total-row"><td>TOTAL:</td><td class="right">S/ {{ number_format((float) $quotation->total, 2) }}</td></tr>
    </table>

    @if ($quotation->notes)
        <div class="section" style="margin-top: 8px;">
            <span class="label">Observaciones</span>
            <div>{{ $quotation->notes }}</div>
        </div>
    @endif

    @include('pdf.partials.cuentas')

    <div class="footnote">
        Cotización válida hasta el {{ $quotation->valid_until->format('d/m/Y') }}. Los precios incluyen IGV según el detalle.
        Este documento no es un comprobante de pago.
    </div>
</body>
</html>
