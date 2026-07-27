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
    <body class="font-sans antialiased text-slate-900">
        <div class="flex min-h-screen flex-col items-center justify-center bg-slate-950 bg-[radial-gradient(ellipse_at_top,_rgba(50,97,220,0.28),_transparent_55%)] px-4 py-10 sm:px-6">
            <a href="/" class="flex flex-col items-center gap-3" wire:navigate>
                <x-application-logo class="h-16 w-16 text-brand-400" />
                <span class="text-center leading-tight">
                    <span class="block text-xl font-extrabold tracking-wide text-white">GRUPO CODISE</span>
                    <span class="block text-xs font-medium uppercase tracking-[0.3em] text-slate-400">Despachos · Facturación</span>
                </span>
            </a>

            <div class="mt-8 w-full max-w-md overflow-hidden rounded-2xl bg-white px-6 py-8 shadow-2xl ring-1 ring-slate-900/5 sm:px-10">
                {{ $slot }}
            </div>

            <p class="mt-8 text-xs text-slate-500">
                GRUPO CODISE S.A.C. · RUC 20600896190
            </p>
        </div>
    </body>
</html>
