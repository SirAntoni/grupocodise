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

        // Solo hay dos roles. Admin: acceso total.
        Role::findOrCreate('admin')->syncPermissions($permissions);

        // Usuario: todo el trabajo diario. Queda fuera la administración
        // (usuarios y series) y el dinero por cobrar — cobranzas y el resumen
        // del panel, que se apoya en el mismo permiso.
        Role::findOrCreate('usuario')->syncPermissions(array_values(array_diff($permissions, [
            'users.manage',
            'series.manage',
            'receivables.view',
            'receivables.manage',
        ])));
    }
}
