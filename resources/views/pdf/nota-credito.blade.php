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
                <div class="company-name">{{ $company['razon_social'] }}</div>
                <div>RUC: {{ $company['ruc'] }}</div>
                <div>{{ $company['address']['direccion'] }}</div>
                <div>{{ $company['address']['distrito'] }} — {{ $company['address']['departamento'] }}</div>
            </td>
            <td align="right">
                <div class="doc-box">
                    <div class="title">NOTA DE CRÉDITO ELECTRÓNICA</div>
                    <div class="number">{{ $creditNote->full_number }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section">
        <table width="100%">
            <tr>
                <td>
                    <span class="label">Señor(es):</span> <strong>{{ $creditNote->invoice->client->business_name }}</strong><br>
                    <span class="label">RUC:</span> {{ $creditNote->invoice->client->ruc }}
                </td>
                <td width="230" style="vertical-align: top;">
                    <span class="label">Fecha de emisión:</span> {{ $creditNote->issue_date->format('d/m/Y') }}<br>
                    <span class="label">Documento afectado:</span> FACTURA {{ $creditNote->affected_full_number ?? $creditNote->invoice->full_number }}<br>
                    <span class="label">Tipo:</span> Anulación de la operación (01)
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <span class="label">Motivo:</span> {{ $creditNote->motive_description }}
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 26px;">#</th>
                <th>Descripción</th>
                <th style="width: 45px;">Unidad</th>
                <th style="width: 60px;">Cantidad</th>
                <th style="width: 70px;">V. unitario</th>
                <th style="width: 70px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($creditNote->invoice->items as $item)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="center">{{ $item->unit_code }}</td>
                    <td class="right">{{ number_format((float) $item->quantity, 2) }}</td>
                    <td class="right">{{ number_format((float) $item->unit_value, 2) }}</td>
                    <td class="right">{{ number_format((float) $item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="legend">{{ \App\Support\NumeroALetras::legend((float) $creditNote->total) }}</div>

    <table class="totals">
        <tr><td class="label">Op. gravadas:</td><td class="right">S/ {{ number_format((float) $creditNote->taxable_amount, 2) }}</td></tr>
        <tr><td class="label">IGV (18%):</td><td class="right">S/ {{ number_format((float) $creditNote->igv, 2) }}</td></tr>
        <tr class="total-row"><td>IMPORTE TOTAL:</td><td class="right">S/ {{ number_format((float) $creditNote->total, 2) }}</td></tr>
    </table>

    @if ($creditNote->electronicDocument?->digest_hash)
        <div class="footnote">Valor resumen: {{ $creditNote->electronicDocument->digest_hash }}</div>
    @endif
    <div class="footnote">Representación impresa de la Nota de Crédito Electrónica.</div>
</body>
</html>
