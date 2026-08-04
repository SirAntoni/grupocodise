{{-- Datos del emisor para la cabecera de todos los PDFs. El logo se imprime
     solo si el archivo existe, así que sin logo el documento sale igual. --}}
@php
    $logo = $company['logo'] ?? null;
    $logoPath = $logo ? public_path($logo) : null;
    $tieneLogo = $logoPath && is_file($logoPath);
@endphp

@if ($tieneLogo)
    <img src="{{ $logoPath }}" alt="" style="max-height: 52px; margin-bottom: 4px;">
@endif
<div class="company-name">{{ $company['razon_social'] }}</div>
<div>RUC: {{ $company['ruc'] }}</div>
<div>{{ $company['address']['direccion'] }}</div>
<div>{{ $company['address']['distrito'] }} — {{ $company['address']['departamento'] }}</div>
@if (! empty($company['telefono']) || ! empty($company['email']))
    <div>
        @if (! empty($company['telefono'])) Teléfono: {{ $company['telefono'] }} @endif
        @if (! empty($company['telefono']) && ! empty($company['email'])) · @endif
        @if (! empty($company['email'])) {{ $company['email'] }} @endif
    </div>
@endif
