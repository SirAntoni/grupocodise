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

            @php
                $accesos = collect([
                    ['permiso' => 'requirements.view', 'ruta' => 'requerimientos.index', 'titulo' => 'Requerimientos', 'detalle' => 'Pedidos que llegan del cliente'],
                    ['permiso' => 'guides.view', 'ruta' => 'guias.index', 'titulo' => 'Guías de despacho', 'detalle' => 'Emitir, imprimir y anular'],
                    ['permiso' => 'products.view', 'ruta' => 'productos.index', 'titulo' => 'Productos y stock', 'detalle' => 'Inventario y kardex'],
                    ['permiso' => 'invoices.view', 'ruta' => 'facturas.index', 'titulo' => 'Facturas', 'detalle' => 'Consolidar guías y facturar'],
                    ['permiso' => 'quotations.view', 'ruta' => 'cotizaciones.index', 'titulo' => 'Cotizaciones', 'detalle' => 'Propuestas y órdenes de compra'],
                    ['permiso' => 'reports.view', 'ruta' => 'reportes.guias', 'titulo' => 'Reportes', 'detalle' => 'Guías por semana, quincena o mes'],
                ])->filter(fn ($acceso) => auth()->user()->can($acceso['permiso']));
            @endphp

            @if ($accesos->isNotEmpty())
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($accesos as $acceso)
                        <a href="{{ route($acceso['ruta']) }}" wire:navigate
                           class="group rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md hover:ring-brand-300">
                            <p class="font-semibold text-slate-900 group-hover:text-brand-700">{{ $acceso['titulo'] }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $acceso['detalle'] }}</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
