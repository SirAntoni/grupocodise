{{-- Cuentas para el pago. Se imprimen solo las configuradas (config/facturacion.php). --}}
@php
    $cuentas = collect($company['cuentas'] ?? [])->filter(fn ($c) => ! empty($c['numero']));
    $yape = $company['yape'] ?? null;
@endphp

@if ($cuentas->isNotEmpty() || $yape)
    <div class="section">
        <span class="label">Cuentas para el pago</span>
        @foreach ($cuentas as $cuenta)
            <div>
                <strong>{{ $cuenta['banco'] }}</strong> — {{ $cuenta['tipo'] }}: {{ $cuenta['numero'] }}
                @if (! empty($cuenta['cci'])) · CCI: {{ $cuenta['cci'] }} @endif
            </div>
        @endforeach
        @if ($yape)
            <div><strong>Yape:</strong> {{ $yape }}</div>
        @endif
    </div>
@endif
