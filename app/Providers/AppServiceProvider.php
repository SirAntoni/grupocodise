<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\FacturacionElectronica::class, fn () => match (config('facturacion.driver')) {
            'fake' => new \App\Services\Facturacion\FakeFacturacionElectronica,
            default => new \App\Services\Facturacion\GreenterFacturacionElectronica,
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // El admin recibe TODOS los permisos vía seeder (no se usa Gate::before:
        // un bypass global rompería las policies que dependen del estado del
        // documento, p. ej. "solo se edita una factura en borrador").

        // Las acciones Livewire (POST /livewire/update) solo re-aplican los
        // middleware persistentes: sin esto, un usuario desactivado seguiría
        // operando con la pestaña abierta.
        \Livewire\Livewire::addPersistentMiddleware([
            \App\Http\Middleware\EnsureUserIsActive::class,
        ]);
    }
}
