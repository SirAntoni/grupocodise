<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: avoid; }
        .header { width: 100%; margin-bottom: 8px; }
        .header td { vertical-align: top; }
        .doc-box { border: 2px solid #111; text-align: center; padding: 8px 14px; width: 220px; }
        .doc-box .title { font-size: 12px; font-weight: bold; }
        .doc-box .number { font-size: 13px; font-weight: bold; margin-top: 4px; }
        .company-name { font-size: 13px; font-weight: bold; }
        .section { border: 1px solid #444; border-radius: 3px; padding: 6px 8px; margin-bottom: 8px; }
        .section .label { color: #555; font-size: 8px; text-transform: uppercase; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.items th, table.items td { border: 1px solid #444; padding: 4px 6px; }
        table.items th { background: #eee; text-transform: uppercase; font-size: 8px; }
        .right { text-align: right; }
        .center { text-align: center; }
        .grid td { padding: 2px 8px 2px 0; vertical-align: top; }
        .signatures { width: 100%; margin-top: 40px; }
        .signatures td { width: 50%; text-align: center; padding: 0 20px; }
        .sign-line { border-top: 1px solid #111; padding-top: 4px; margin-top: 50px; font-size: 9px; }
        .copy-label { text-align: right; font-size: 9px; font-weight: bold; color: #555; margin-bottom: 4px; }
        .footnote { font-size: 8px; color: #555; margin-top: 6px; }
    </style>
</head>
<body>
@foreach ($copies as $copyLabel)
    <div class="page">
        <div class="copy-label">{{ $copyLabel }}</div>

        <table class="header">
            <tr>
                <td>
                    @include('pdf.partials.emisor')
                </td>
                <td align="right">
                    <div class="doc-box">
                        <div class="title">GUÍA DE REMISIÓN REMITENTE<br>ELECTRÓNICA</div>
                        <div class="number">{{ $guide->full_number }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section">
            <div class="label">Destinatario</div>
            <table class="grid">
                <tr>
                    <td><strong>{{ $guide->client->business_name }}</strong></td>
                    <td>RUC: {{ $guide->client->ruc }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="label">Datos del traslado</div>
            <table class="grid" width="100%">
                <tr>
                    <td><span class="label">Fecha emisión:</span> {{ $guide->issue_date?->format('d/m/Y') }}</td>
                    <td><span class="label">Inicio de traslado:</span> {{ $guide->transfer_date?->format('d/m/Y') }}</td>
                    <td><span class="label">Motivo:</span> {{ \App\Support\SunatCatalogs::TRANSFER_REASONS[$guide->transfer_reason_code] ?? $guide->transfer_reason_code }}</td>
                </tr>
                <tr>
                    <td colspan="2"><span class="label">Punto de partida:</span> {{ $guide->origin_address }}</td>
                    <td><span class="label">Peso bruto:</span> {{ number_format((float) $guide->total_weight, 2) }} kg</td>
                </tr>
                <tr>
                    <td colspan="2"><span class="label">Punto de llegada:</span> {{ $guide->delivery_address }} @if ($guide->district) — {{ $guide->district }} @endif</td>
                    <td>@if ($guide->packages_count)<span class="label">Bultos:</span> {{ $guide->packages_count }}@endif</td>
                </tr>
                <tr>
                    <td colspan="3">
                        <span class="label">Transporte:</span>
                        @if ($guide->transport_mode?->value === 'publico')
                            Público — {{ $guide->carrier_name }} (RUC {{ $guide->carrier_ruc }})
                        @elseif ($guide->transport_mode?->value === 'privado')
                            Privado — Placa {{ $guide->vehicle_plate }} — Conductor: {{ $guide->driver_first_names }} {{ $guide->driver_last_names }} ({{ $guide->driver_document }}) — Licencia: {{ $guide->driver_license }}
                        @endif
                    </td>
                </tr>
                @if ($guide->crew_chief)
                    <tr><td colspan="3"><span class="label">Jefe de cuadrilla:</span> {{ $guide->crew_chief }}</td></tr>
                @endif
                @if ($guide->requirement)
                    <tr><td colspan="3"><span class="label">Requerimiento:</span> {{ $guide->requirement->code }}</td></tr>
                @endif
            </table>
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th style="width: 30px;">#</th>
                    <th style="width: 70px;">Código</th>
                    <th>Descripción</th>
                    <th style="width: 60px;">Unidad</th>
                    <th style="width: 70px;">Solicitada</th>
                    <th style="width: 70px;">Despachada</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($guide->items as $item)
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ $item->product->code }}</td>
                        <td>{{ $item->description }}</td>
                        <td class="center">{{ $item->unit_code }}</td>
                        <td class="right">{{ number_format((float) $item->quantity_requested, 2) }}</td>
                        <td class="right">{{ number_format((float) $item->quantity_dispatched, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($guide->notes)
            <div class="section">
                <div class="label">Observaciones</div>
                {{ $guide->notes }}
            </div>
        @endif

        @if ($guide->status === \App\Enums\DispatchGuideStatus::Annulled)
            <div class="section" style="border-color: #b91c1c; color: #b91c1c;">
                <strong>GUÍA ANULADA</strong> — {{ $guide->annulment_reason }}
            </div>
        @endif

        <table class="signatures">
            <tr>
                <td><div class="sign-line">RECIBÍ CONFORME<br>Nombre, DNI y firma del cliente</div></td>
                <td><div class="sign-line">DESPACHADO POR<br>{{ $company['razon_social'] }}</div></td>
            </tr>
        </table>

        @if ($guide->electronicDocument?->digest_hash)
            <div class="footnote">Valor resumen: {{ $guide->electronicDocument->digest_hash }}</div>
        @endif
        <div class="footnote">Representación impresa de la Guía de Remisión Remitente Electrónica.</div>
    </div>
@endforeach
</body>
</html>
