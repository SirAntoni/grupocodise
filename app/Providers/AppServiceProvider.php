<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
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
        // El rol admin tiene acceso total sin enumerar permisos.
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('admin') ? true : null;
        });

        // Las acciones Livewire (POST /livewire/update) solo re-aplican los
        // middleware persistentes: sin esto, un usuario desactivado seguiría
        // operando con la pestaña abierta.
        \Livewire\Livewire::addPersistentMiddleware([
            \App\Http\Middleware\EnsureUserIsActive::class,
        ]);
    }
}
