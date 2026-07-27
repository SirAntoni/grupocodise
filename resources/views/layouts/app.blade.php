<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'GRUPO CODISE') }}</title>

        <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'%3E%3Cpath d='M24 3 42 13v22L24 45 6 35V13L24 3Z' fill='%23253a82'/%3E%3Cpath d='M15 8.5 33 18.5v7L15 15.5v-7Z' fill='%23f59e0b'/%3E%3C/svg%3E">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-100 text-slate-900">
        <div class="min-h-screen">
            <livewire:layout.navigation />

            <div class="lg:pl-72">
                <!-- Cabecera para vistas Blade clásicas (x-app-layout) -->
                @if (isset($header))
                    <header class="bg-white border-b border-slate-200">
                        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>

        {{-- Modal global de confirmación (homologa los confirm() nativos) --}}
        <div x-data x-show="$store.confirm.show" x-cloak
             class="fixed inset-0 z-[70] flex items-end justify-center sm:items-center sm:p-4"
             @keydown.escape.window="$store.confirm.cancel()">
            <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" @click="$store.confirm.cancel()"></div>
            <div class="relative w-full rounded-t-2xl bg-white p-6 shadow-2xl sm:max-w-md sm:rounded-2xl"
                 x-transition.origin.bottom.duration.150ms>
                <div class="flex items-start gap-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                          :class="$store.confirm.danger ? 'bg-red-100 text-red-600' : 'bg-brand-100 text-brand-700'">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-slate-900" x-text="$store.confirm.title"></h3>
                        <p class="mt-1 text-sm text-slate-600" x-text="$store.confirm.message"></p>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" @click="$store.confirm.cancel()"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="button" @click="$store.confirm.proceed()"
                            class="inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition"
                            :class="$store.confirm.danger ? 'bg-red-600 hover:bg-red-700' : 'bg-brand-700 hover:bg-brand-800'"
                            x-text="$store.confirm.confirmText"></button>
                </div>
            </div>
        </div>
    </body>
</html>
