<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'users.manage',
            'series.manage',
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
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // Admin: acceso total (vía Gate::before, pero se asignan igual por claridad).
        Role::findOrCreate('admin')->syncPermissions($permissions);

        // Operaciones: el equipo trabaja el ciclo completo (requerimientos, guías,
        // stock, productos, cotizaciones, OC, facturas, cobranza y reportes).
        // Es el rol de todos los días; queda fuera solo la administración.
        Role::findOrCreate('operaciones')->syncPermissions(
            array_values(array_diff($permissions, ['users.manage', 'series.manage']))
        );

        // Los tres roles siguientes son recortes del anterior, para cuando se
        // necesite dar acceso parcial a alguien puntual.

        // Proveedores: registra pedidos/requerimientos.
        Role::findOrCreate('proveedores')->syncPermissions([
            'clients.view', 'clients.manage',
            'products.view',
            'requirements.view', 'requirements.manage',
            'guides.view',
        ]);

        // Logística: guías, stock, productos.
        Role::findOrCreate('logistica')->syncPermissions([
            'clients.view',
            'products.view', 'products.manage',
            'stock.view', 'stock.manage',
            'requirements.view',
            'guides.view', 'guides.manage',
            'reports.view',
        ]);

        // Pagos: facturas, OC, cotizaciones y cobranzas.
        Role::findOrCreate('pagos')->syncPermissions([
            'clients.view',
            'guides.view',
            'invoices.view', 'invoices.manage',
            'quotations.view', 'quotations.manage',
            'purchase-orders.view', 'purchase-orders.manage',
            'receivables.view', 'receivables.manage',
            'reports.view',
        ]);
    }
}
