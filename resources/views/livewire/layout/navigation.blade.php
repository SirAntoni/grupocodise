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

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" wire:navigate>
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-6 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                        Panel
                    </x-nav-link>

                    @can('requirements.view')
                        <x-nav-link :href="route('requerimientos.index')" :active="request()->routeIs('requerimientos.*')" wire:navigate>
                            Requerimientos
                        </x-nav-link>
                    @endcan

                    @canany(['products.view', 'stock.view', 'guides.view'])
                        <div class="inline-flex items-center">
                            <x-dropdown align="left" width="48">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('productos.*', 'kardex.*', 'guias.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500' }} text-sm font-medium leading-5 hover:text-gray-700 hover:border-gray-300 focus:outline-none transition duration-150 ease-in-out">
                                        Logística
                                        <svg class="fill-current h-4 w-4 ms-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    @can('guides.view')
                                        <x-dropdown-link :href="route('guias.index')" wire:navigate>Guías de despacho</x-dropdown-link>
                                    @endcan
                                    @can('products.view')
                                        <x-dropdown-link :href="route('productos.index')" wire:navigate>Productos</x-dropdown-link>
                                    @endcan
                                    @can('stock.view')
                                        <x-dropdown-link :href="route('kardex.index')" wire:navigate>Kardex de stock</x-dropdown-link>
                                    @endcan
                                </x-slot>
                            </x-dropdown>
                        </div>
                    @endcanany

                    @canany(['quotations.view', 'purchase-orders.view', 'invoices.view'])
                        <div class="inline-flex items-center">
                            <x-dropdown align="left" width="48">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('facturas.*', 'cotizaciones.*', 'ordenes-compra.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500' }} text-sm font-medium leading-5 hover:text-gray-700 hover:border-gray-300 focus:outline-none transition duration-150 ease-in-out">
                                        Ventas
                                        <svg class="fill-current h-4 w-4 ms-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    @can('invoices.view')
                                        <x-dropdown-link :href="route('facturas.index')" wire:navigate>Facturas</x-dropdown-link>
                                    @endcan
                                    @can('quotations.view')
                                        <x-dropdown-link :href="route('cotizaciones.index')" wire:navigate>Cotizaciones</x-dropdown-link>
                                    @endcan
                                    @can('purchase-orders.view')
                                        <x-dropdown-link :href="route('ordenes-compra.index')" wire:navigate>Órdenes de compra</x-dropdown-link>
                                    @endcan
                                </x-slot>
                            </x-dropdown>
                        </div>
                    @endcanany

                    @can('receivables.view')
                        <div class="inline-flex items-center">
                            <x-dropdown align="left" width="48">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('cobranzas.*', 'pagos.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500' }} text-sm font-medium leading-5 hover:text-gray-700 hover:border-gray-300 focus:outline-none transition duration-150 ease-in-out">
                                        Cobranzas
                                        <svg class="fill-current h-4 w-4 ms-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    <x-dropdown-link :href="route('cobranzas.index')" wire:navigate>Tablero de cobranza</x-dropdown-link>
                                    <x-dropdown-link :href="route('pagos.index')" wire:navigate>Historial de pagos</x-dropdown-link>
                                </x-slot>
                            </x-dropdown>
                        </div>
                    @endcan

                    @can('reports.view')
                        <div class="inline-flex items-center">
                            <x-dropdown align="left" width="48">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('reportes.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500' }} text-sm font-medium leading-5 hover:text-gray-700 hover:border-gray-300 focus:outline-none transition duration-150 ease-in-out">
                                        Reportes
                                        <svg class="fill-current h-4 w-4 ms-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    <x-dropdown-link :href="route('reportes.guias')" wire:navigate>Guías por quincena</x-dropdown-link>
                                    <x-dropdown-link :href="route('reportes.diferencias')" wire:navigate>Diferencias sol. vs desp.</x-dropdown-link>
                                </x-slot>
                            </x-dropdown>
                        </div>
                    @endcan

                    @canany(['users.manage', 'series.manage', 'clients.view'])
                        <div class="inline-flex items-center">
                            <x-dropdown align="left" width="48">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('usuarios.*', 'series.*', 'clientes.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500' }} text-sm font-medium leading-5 hover:text-gray-700 hover:border-gray-300 focus:outline-none transition duration-150 ease-in-out">
                                        Administración
                                        <svg class="fill-current h-4 w-4 ms-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    @can('clients.view')
                                        <x-dropdown-link :href="route('clientes.index')" wire:navigate>Clientes</x-dropdown-link>
                                    @endcan
                                    @can('users.manage')
                                        <x-dropdown-link :href="route('usuarios.index')" wire:navigate>Usuarios</x-dropdown-link>
                                    @endcan
                                    @can('series.manage')
                                        <x-dropdown-link :href="route('series.index')" wire:navigate>Series de comprobantes</x-dropdown-link>
                                    @endcan
                                </x-slot>
                            </x-dropdown>
                        </div>
                    @endcanany
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            Mi perfil
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                Cerrar sesión
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                Panel
            </x-responsive-nav-link>
            @can('requirements.view')
                <x-responsive-nav-link :href="route('requerimientos.index')" :active="request()->routeIs('requerimientos.*')" wire:navigate>
                    Requerimientos
                </x-responsive-nav-link>
            @endcan
            @can('guides.view')
                <x-responsive-nav-link :href="route('guias.index')" :active="request()->routeIs('guias.*')" wire:navigate>
                    Guías de despacho
                </x-responsive-nav-link>
            @endcan
            @can('products.view')
                <x-responsive-nav-link :href="route('productos.index')" :active="request()->routeIs('productos.*')" wire:navigate>
                    Productos
                </x-responsive-nav-link>
            @endcan
            @can('stock.view')
                <x-responsive-nav-link :href="route('kardex.index')" :active="request()->routeIs('kardex.*')" wire:navigate>
                    Kardex de stock
                </x-responsive-nav-link>
            @endcan
            @can('invoices.view')
                <x-responsive-nav-link :href="route('facturas.index')" :active="request()->routeIs('facturas.*')" wire:navigate>
                    Facturas
                </x-responsive-nav-link>
            @endcan
            @can('quotations.view')
                <x-responsive-nav-link :href="route('cotizaciones.index')" :active="request()->routeIs('cotizaciones.*')" wire:navigate>
                    Cotizaciones
                </x-responsive-nav-link>
            @endcan
            @can('purchase-orders.view')
                <x-responsive-nav-link :href="route('ordenes-compra.index')" :active="request()->routeIs('ordenes-compra.*')" wire:navigate>
                    Órdenes de compra
                </x-responsive-nav-link>
            @endcan
            @can('receivables.view')
                <x-responsive-nav-link :href="route('cobranzas.index')" :active="request()->routeIs('cobranzas.*')" wire:navigate>
                    Tablero de cobranza
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('pagos.index')" :active="request()->routeIs('pagos.*')" wire:navigate>
                    Historial de pagos
                </x-responsive-nav-link>
            @endcan
            @can('reports.view')
                <x-responsive-nav-link :href="route('reportes.guias')" :active="request()->routeIs('reportes.guias')" wire:navigate>
                    Reporte de guías por quincena
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('reportes.diferencias')" :active="request()->routeIs('reportes.diferencias')" wire:navigate>
                    Reporte de diferencias
                </x-responsive-nav-link>
            @endcan
            @can('clients.view')
                <x-responsive-nav-link :href="route('clientes.index')" :active="request()->routeIs('clientes.*')" wire:navigate>
                    Clientes
                </x-responsive-nav-link>
            @endcan
            @can('users.manage')
                <x-responsive-nav-link :href="route('usuarios.index')" :active="request()->routeIs('usuarios.*')" wire:navigate>
                    Usuarios
                </x-responsive-nav-link>
            @endcan
            @can('series.manage')
                <x-responsive-nav-link :href="route('series.index')" :active="request()->routeIs('series.*')" wire:navigate>
                    Series de comprobantes
                </x-responsive-nav-link>
            @endcan
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="font-medium text-sm text-gray-500">{{ auth()->user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    Mi perfil
                </x-responsive-nav-link>

                <!-- Authentication -->
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>
                        Cerrar sesión
                    </x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
