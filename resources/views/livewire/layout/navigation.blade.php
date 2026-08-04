<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
    {{-- Velo móvil --}}
    <div x-show="sidebarOpen" x-cloak x-transition.opacity
         class="fixed inset-0 z-40 bg-slate-950/60 lg:hidden"
         @click="sidebarOpen = false"></div>

    {{-- Barra lateral --}}
    <aside class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-slate-900 text-slate-100 transition-transform duration-200 max-lg:-translate-x-full"
           :style="sidebarOpen ? 'transform: translateX(0);' : ''">
        <div class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-800 px-5">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3">
                <x-application-logo class="h-9 w-9 text-brand-400" />
                <span class="leading-tight">
                    <span class="block text-sm font-extrabold tracking-wide text-white">GRUPO CODISE</span>
                    <span class="block text-[11px] font-medium uppercase tracking-widest text-slate-400">Despachos · Facturación</span>
                </span>
            </a>
            <button class="ms-auto rounded-md p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white lg:hidden"
                    @click="sidebarOpen = false" aria-label="Cerrar menú">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <nav class="sidebar-scroll flex-1 space-y-6 overflow-y-auto px-4 py-5">
            <div class="space-y-1">
                <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/></svg>
                    Panel
                </x-sidebar-link>
            </div>

            @canany(['requirements.view', 'guides.view'])
                <div>
                    <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-widest text-slate-500">Operaciones</p>
                    <div class="space-y-1">
                        @can('requirements.view')
                            <x-sidebar-link :href="route('requerimientos.index')" :active="request()->routeIs('requerimientos.*')" wire:navigate>
                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M9 8h6M5.25 4.5h13.5c.621 0 1.125.504 1.125 1.125v12.75c0 .621-.504 1.125-1.125 1.125H5.25c-.621 0-1.125-.504-1.125-1.125V5.625c0-.621.504-1.125 1.125-1.125Z"/></svg>
                                Requerimientos
                            </x-sidebar-link>
                        @endcan
                        @can('guides.view')
                            <x-sidebar-link :href="route('guias.index')" :active="request()->routeIs('guias.*')" wire:navigate>
                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.9 17.9 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                                Guías de despacho
                            </x-sidebar-link>
                        @endcan
                    </div>
                </div>
            @endcanany

            @canany(['products.view', 'stock.view'])
                <div>
                    <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-widest text-slate-500">Inventario</p>
                    <div class="space-y-1">
                        @can('products.view')
                            <x-sidebar-link :href="route('productos.index')" :active="request()->routeIs('productos.*')" wire:navigate>
                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                                Productos
                            </x-sidebar-link>
                        @endcan
                        @can('stock.view')
                            <x-sidebar-link :href="route('kardex.index')" :active="request()->routeIs('kardex.*')" wire:navigate>
                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
                                Kardex de stock
                            </x-sidebar-link>
                        @endcan
                    </div>
                </div>
            @endcanany

            @canany(['quotations.view', 'purchase-orders.view', 'invoices.view'])
                <div>
                    <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-widest text-slate-500">Ventas</p>
                    <div class="space-y-1">
                        @can('quotations.view')
                            <x-sidebar-link :href="route('cotizaciones.index')" :active="request()->routeIs('cotizaciones.*')" wire:navigate>
                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                                Cotizaciones
                            </x-sidebar-link>
                        @endcan
                        @can('purchase-orders.view')
                            <x-sidebar-link :href="route('ordenes-compra.index')" :active="request()->routeIs('ordenes-compra.*')" wire:navigate>
                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z"/></svg>
                                Órdenes de compra
                            </x-sidebar-link>
                        @endcan
                        @can('invoices.view')
                            <x-sidebar-link :href="route('facturas.index')" :active="request()->routeIs('facturas.*')" wire:navigate>
                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25v2.25m3-4.5v4.5m3-6.75v6.75m-10.5 3.75h13.5c.621 0 1.125-.504 1.125-1.125V4.875c0-.621-.504-1.125-1.125-1.125H5.25c-.621 0-1.125.504-1.125 1.125v14.25c0 .621.504 1.125 1.125 1.125Z"/></svg>
                                Facturas
                            </x-sidebar-link>
                        @endcan
                    </div>
                </div>
            @endcanany

            @can('receivables.view')
                <div>
                    <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-widest text-slate-500">Cobranzas</p>
                    <div class="space-y-1">
                        <x-sidebar-link :href="route('cobranzas.index')" :active="request()->routeIs('cobranzas.*')" wire:navigate>
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            Tablero de cobranza
                        </x-sidebar-link>
                        <x-sidebar-link :href="route('pagos.index')" :active="request()->routeIs('pagos.*')" wire:navigate>
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
                            Historial de pagos
                        </x-sidebar-link>
                    </div>
                </div>
            @endcan

            @can('reports.view')
                <div>
                    <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-widest text-slate-500">Reportes</p>
                    <div class="space-y-1">
                        <x-sidebar-link :href="route('reportes.guias')" :active="request()->routeIs('reportes.guias')" wire:navigate>
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                            Guías por periodo
                        </x-sidebar-link>
                        <x-sidebar-link :href="route('reportes.diferencias')" :active="request()->routeIs('reportes.diferencias')" wire:navigate>
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5 9 7.5l4.5 4.5L21 4.5m0 0h-5.25M21 4.5v5.25M3 20.25h18"/></svg>
                            Diferencias sol./desp.
                        </x-sidebar-link>
                    </div>
                </div>
            @endcan

            @canany(['clients.view', 'users.manage', 'series.manage'])
                <div>
                    <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-widest text-slate-500">Administración</p>
                    <div class="space-y-1">
                        @can('clients.view')
                            <x-sidebar-link :href="route('clientes.index')" :active="request()->routeIs('clientes.*')" wire:navigate>
                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h1.5c.621 0 1.125.504 1.125 1.125V21"/></svg>
                                Clientes
                            </x-sidebar-link>
                        @endcan
                        @can('users.manage')
                            <x-sidebar-link :href="route('usuarios.index')" :active="request()->routeIs('usuarios.*')" wire:navigate>
                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                                Usuarios
                            </x-sidebar-link>
                        @endcan
                        @can('series.manage')
                            <x-sidebar-link :href="route('series.index')" :active="request()->routeIs('series.*')" wire:navigate>
                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 0 1 1.037-.443 48.282 48.282 0 0 0 5.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/></svg>
                                Series
                            </x-sidebar-link>
                        @endcan
                    </div>
                </div>
            @endcanany
            <div>
                <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-widest text-slate-500">Ayuda</p>
                <div class="space-y-1">
                    <x-sidebar-link :href="route('manual')" :active="request()->routeIs('manual')" wire:navigate>
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>
                        Manual de usuario
                    </x-sidebar-link>
                </div>
            </div>
        </nav>

        <div class="border-t border-slate-800 px-5 py-3">
            <p class="text-[11px] text-slate-500">RUC 20600896190 · GRUPO CODISE S.A.C.</p>
        </div>
    </aside>

    {{-- Barra superior --}}
    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white lg:pl-72">
        <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
            <button class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 lg:hidden"
                    @click="sidebarOpen = true" aria-label="Abrir menú">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
            </button>

            <div class="flex items-center gap-2 lg:hidden">
                <x-application-logo class="h-7 w-7 text-brand-700" />
                <span class="text-sm font-extrabold tracking-wide text-slate-900">GRUPO CODISE</span>
            </div>

            <div class="ms-auto">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-3 rounded-full py-1.5 pe-2 ps-1.5 text-sm transition hover:bg-slate-100">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-700 text-xs font-bold text-white">
                                {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                            </span>
                            <span class="hidden text-start sm:block">
                                <span class="block text-sm font-semibold text-slate-800"
                                      x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name"
                                      x-on:profile-updated.window="name = $event.detail.name"></span>
                                <span class="block text-xs text-slate-500">{{ auth()->user()->roleLabel() }}</span>
                            </span>
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            Mi perfil
                        </x-dropdown-link>

                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                Cerrar sesión
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </header>
</div>
