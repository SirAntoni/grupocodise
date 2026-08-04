<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

/**
 * El equipo trabaja el ciclo completo, así que el rol de uso diario es
 * "operaciones": todo menos la administración.
 */

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('el rol operaciones abarca todo el ciclo operativo menos la administración', function () {
    $permisos = Role::findByName('operaciones')->permissions->pluck('name');

    expect($permisos)->toHaveCount(19)
        ->and($permisos)->not->toContain('users.manage')
        ->and($permisos)->not->toContain('series.manage');

    foreach ([
        'clients.view', 'clients.manage',
        'products.view', 'products.manage',
        'stock.view', 'stock.manage',
        'requirements.view', 'requirements.manage',
        'guides.view', 'guides.manage',
        'invoices.view', 'invoices.manage',
        'quotations.view', 'quotations.manage',
        'purchase-orders.view', 'purchase-orders.manage',
        'receivables.view', 'receivables.manage',
        'reports.view',
    ] as $permiso) {
        expect($permisos)->toContain($permiso);
    }
});

it('operaciones entra a todos los módulos de trabajo', function () {
    $user = User::factory()->create();
    $user->assignRole('operaciones');
    $this->actingAs($user);

    foreach ([
        'requerimientos.index', 'requerimientos.crear',
        'guias.index',
        'productos.index', 'kardex.index',
        'clientes.index',
        'cotizaciones.index', 'cotizaciones.crear',
        'ordenes-compra.index',
        'facturas.index', 'facturas.crear',
        'cobranzas.index', 'pagos.index',
        'reportes.guias', 'reportes.diferencias',
        'dashboard', 'manual',
    ] as $ruta) {
        $this->get(route($ruta))->assertOk();
    }
});

it('operaciones no puede administrar usuarios ni series', function () {
    $user = User::factory()->create();
    $user->assignRole('operaciones');
    $this->actingAs($user);

    $this->get(route('usuarios.index'))->assertForbidden();
    $this->get(route('series.index'))->assertForbidden();
});

it('el comando migra los roles antiguos y no toca al administrador', function () {
    $antiguo = User::factory()->create();
    $antiguo->assignRole('logistica');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->artisan('roles:migrar-operaciones')->assertSuccessful();

    expect($antiguo->fresh()->getRoleNames()->all())->toBe(['operaciones'])
        ->and($admin->fresh()->getRoleNames()->all())->toBe(['admin']);

    // Idempotente: una segunda corrida ya no encuentra a nadie.
    $this->artisan('roles:migrar-operaciones')
        ->expectsOutputToContain('0 usuario(s) migrados')
        ->assertSuccessful();
});

it('el seeder es idempotente y no borra las asignaciones existentes', function () {
    $user = User::factory()->create();
    $user->assignRole('operaciones');

    $rolesAntes = Role::query()->count();
    $permisosAntes = \Spatie\Permission\Models\Permission::query()->count();

    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::query()->count())->toBe($rolesAntes)
        ->and(\Spatie\Permission\Models\Permission::query()->count())->toBe($permisosAntes)
        ->and($user->fresh()->hasRole('operaciones'))->toBeTrue();
});
