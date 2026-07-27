<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200 p-5">
        <div class="text-sm text-slate-500">Cuentas por cobrar abiertas</div>
        <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $summary['total'] }}</div>
        <div class="text-sm text-slate-500 mt-1">Saldo: S/ {{ number_format((float) $summary['balance'], 2) }}</div>
    </div>
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200 p-5">
        <div class="flex items-center gap-2">
            <span class="h-3 w-3 rounded-full bg-green-500"></span>
            <span class="text-sm text-slate-500">En plazo</span>
        </div>
        <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $summary['verde'] }}</div>
    </div>
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200 p-5">
        <div class="flex items-center gap-2">
            <span class="h-3 w-3 rounded-full bg-amber-400"></span>
            <span class="text-sm text-slate-500">Por vencer (≤ 5 días)</span>
        </div>
        <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $summary['amarillo'] }}</div>
    </div>
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200 p-5">
        <div class="flex items-center gap-2">
            <span class="h-3 w-3 rounded-full bg-red-500"></span>
            <span class="text-sm text-slate-500">Vencidas</span>
        </div>
        <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $summary['rojo'] }}</div>
    </div>
</div>
