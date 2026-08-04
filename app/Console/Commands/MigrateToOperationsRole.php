<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Pasa al rol único "operaciones" a quienes tenían los roles operativos
 * recortados. Idempotente: al terminar no queda nadie con los roles viejos,
 * así que una segunda corrida no cambia nada.
 */
class MigrateToOperationsRole extends Command
{
    protected $signature = 'roles:migrar-operaciones {--dry-run : Muestra los cambios sin aplicarlos}';

    protected $description = 'Reasigna al rol operaciones a los usuarios con roles operativos antiguos (proveedores, logistica, pagos)';

    public function handle(): int
    {
        if (! Role::query()->where('name', 'operaciones')->exists()) {
            $this->error('No existe el rol operaciones. Corre primero: php artisan db:seed --class=RolesAndPermissionsSeeder --force');

            return self::FAILURE;
        }

        $legacy = Role::query()
            ->whereIn('name', ['proveedores', 'logistica', 'pagos'])
            ->pluck('name')
            ->all();

        $users = $legacy === []
            ? collect()
            : User::role($legacy)->orderBy('id')->get();

        $migrated = 0;

        foreach ($users as $user) {
            $before = $user->getRoleNames();

            // Un admin con rol operativo extra conservaría su acceso total al
            // sincronizar, así que mejor no tocarlo y avisar.
            if ($before->contains('admin')) {
                $this->warn("- {$user->email}: tiene rol admin, se omite.");

                continue;
            }

            $this->line("- {$user->email}: [{$before->implode(', ')}] → operaciones");

            if (! $this->option('dry-run')) {
                $user->syncRoles(['operaciones']);
            }

            $migrated++;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->newLine();
        $this->info($this->option('dry-run')
            ? "{$migrated} usuario(s) cambiarían de rol (simulación, no se aplicó nada)."
            : "{$migrated} usuario(s) migrados al rol operaciones.");

        return self::SUCCESS;
    }
}
