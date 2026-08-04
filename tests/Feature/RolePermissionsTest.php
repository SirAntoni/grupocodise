<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Hay dos roles: "usuario" hace el trabajo diario y "admin" además lleva
 * cobranzas, el resumen del panel y la administración (usuarios y series).
 */

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('solo existen los roles usuario y admin', function () {
    expect(Role::query()->orderBy('name')->pluck('name')->all())->toBe(['admin', 'usuario']);
});

it('el rol usuario cubre el trabajo diario pero no cobranzas ni administración', function () {
    $permisos = Role::findByName('usuario')->permissions->pluck('name');

    expect($permisos)->toHaveCount(17)
        ->and($permisos)->not->toContain('users.manage')
        ->and($permisos)->not->toContain('series.manage')
        ->and($permisos)->not->toContain('receivables.view')
        ->and($permisos)->not->toContain('receivables.manage');

    foreach ([
        'clients.view', 'clients.manage',
        'products.view', 'products.manage',
        'stock.view', 'stock.manage',
        'requirements.view', 'requirements.manage',
        'guides.view', 'guides.manage',
        'invoices.view', 'invoices.manage',
        'quotations.view', 'quotations.manage',
        'purchase-orders.view', 'purchase-orders.manage',
        'reports.view',
    ] as $permiso) {
        expect($permisos)->toContain($permiso);
    }
});

it('el usuario entra a todos sus módulos de trabajo', function () {
    $user = User::factory()->create();
    $user->assignRole('usuario');
    $this->actingAs($user);

    foreach ([
        'requerimientos.index', 'requerimientos.crear',
        'guias.index',
        'productos.index', 'kardex.index',
        'clientes.index',
        'cotizaciones.index', 'cotizaciones.crear',
        'ordenes-compra.index',
        'facturas.index', 'facturas.crear',
        'reportes.guias', 'reportes.diferencias',
        'dashboard', 'manual',
    ] as $ruta) {
        $this->get(route($ruta))->assertOk();
    }
});

it('el usuario no ve cobranzas, el resumen del panel ni la administración', function () {
    $user = User::factory()->create();
    $user->assignRole('usuario');
    $this->actingAs($user);

    $this->get(route('cobranzas.index'))->assertForbidden();
    $this->get(route('pagos.index'))->assertForbidden();
    $this->get(route('usuarios.index'))->assertForbidden();
    $this->get(route('series.index'))->assertForbidden();

    // El resumen de cobranza del panel se apoya en el mismo permiso.
    $this->get(route('dashboard'))->assertOk()->assertDontSee('Cobranza');
});

it('el administrador sí ve cobranzas y la administración', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);

    foreach (['cobranzas.index', 'pagos.index', 'usuarios.index', 'series.index'] as $ruta) {
        $this->get(route($ruta))->assertOk();
    }
});

it('el comando normaliza los roles antiguos y no toca al administrador', function () {
    // Cuenta con un rol de la etapa anterior del sistema.
    Role::findOrCreate('logistica');
    $antiguo = User::factory()->create();
    $antiguo->assignRole('logistica');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->artisan('roles:normalizar')->assertSuccessful();

    expect($antiguo->fresh()->getRoleNames()->all())->toBe(['usuario'])
        ->and($admin->fresh()->getRoleNames()->all())->toBe(['admin'])
        // El rol viejo queda eliminado al no tener a nadie.
        ->and(Role::query()->where('name', 'logistica')->exists())->toBeFalse();

    $this->artisan('roles:normalizar')
        ->expectsOutputToContain('0 usuario(s) migrados')
        ->assertSuccessful();
});

it('el seeder es idempotente y no borra las asignaciones existentes', function () {
    $user = User::factory()->create();
    $user->assignRole('usuario');

    $rolesAntes = Role::query()->count();
    $permisosAntes = Permission::query()->count();

    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::query()->count())->toBe($rolesAntes)
        ->and(Permission::query()->count())->toBe($permisosAntes)
        ->and($user->fresh()->hasRole('usuario'))->toBeTrue();
});
