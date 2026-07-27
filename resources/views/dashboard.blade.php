<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <div class="mb-6 overflow-hidden rounded-2xl bg-slate-900 bg-[radial-gradient(ellipse_at_top_right,_rgba(50,97,220,0.45),_transparent_60%)] px-6 py-8 text-white shadow-lg sm:px-8">
            <p class="text-sm font-medium uppercase tracking-widest text-brand-300">{{ now()->translatedFormat('l d \d\e F \d\e Y') }}</p>
            <h1 class="mt-2 text-2xl font-bold sm:text-3xl">Hola, {{ auth()->user()->name }} 👋</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-300">
                Bienvenido al sistema de despachos y facturación de GRUPO CODISE.
                Usa el menú lateral para acceder a tus módulos.
            </p>
        </div>

        <div class="space-y-5">
            @can('receivables.view')
                <livewire:dashboard.collection-summary />
            @endcan
        </div>
    </div>
</x-app-layout>
