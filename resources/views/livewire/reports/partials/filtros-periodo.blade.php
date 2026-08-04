{{-- Controles de periodo compartidos por los reportes (trait HasReportPeriod). --}}
<div>
    <x-input-label value="Periodo" />
    <select wire:model.live="periodType"
            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
        @foreach ($periods as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>
</div>

@if ($periodType !== 'libre')
    <div>
        <x-input-label value="Año" />
        <select wire:model.live="year"
                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
            @for ($y = now()->year; $y >= now()->year - 4; $y--)
                <option value="{{ $y }}">{{ $y }}</option>
            @endfor
        </select>
    </div>
@endif

@if (in_array($periodType, ['quincenal', 'mensual']))
    <div>
        <x-input-label value="Mes" />
        <select wire:model.live="month"
                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
            @foreach (['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] as $i => $name)
                <option value="{{ $i + 1 }}">{{ $name }}</option>
            @endforeach
        </select>
    </div>
@endif

@if ($periodType === 'quincenal')
    <div>
        <x-input-label value="Quincena" />
        <select wire:model.live="fortnight"
                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
            <option value="1">1.ª (del 1 al 15)</option>
            <option value="2">2.ª (del 16 a fin de mes)</option>
        </select>
    </div>
@endif

@if ($periodType === 'semanal')
    <div>
        <x-input-label value="Semana" />
        <select wire:model.live="week"
                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
            @foreach ($weeks as $number => $label)
                <option value="{{ $number }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
@endif

@if ($periodType === 'libre')
    <div>
        <x-input-label value="Desde" />
        <x-text-input type="date" class="mt-1 block w-full" wire:model.live="from" />
    </div>
    <div>
        <x-input-label value="Hasta" />
        <x-text-input type="date" class="mt-1 block w-full" wire:model.live="until" />
    </div>
@endif
