<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Deja el sistema con los dos roles vigentes (admin y usuario): mueve a
 * "usuario" a quien tenga alguno de los roles antiguos y borra los que queden
 * sin nadie. Idempotente: la segunda corrida ya no encuentra nada que hacer.
 */
class NormalizeRoles extends Command
{
    protected $signature = 'roles:normalizar {--dry-run : Muestra los cambios sin aplicarlos}';

    protected $description = 'Migra los roles antiguos al rol usuario y elimina los que queden vacíos';

    /** Roles que existieron antes de unificar en admin + usuario. */
    protected array $legacy = ['proveedores', 'logistica', 'pagos', 'operaciones'];

    public function handle(): int
    {
        if (! Role::query()->where('name', 'usuario')->exists()) {
            $this->error('No existe el rol usuario. Corre primero: php artisan db:seed --class=RolesAndPermissionsSeeder --force');

            return self::FAILURE;
        }

        $presentes = Role::query()->whereIn('name', $this->legacy)->pluck('name')->all();

        $usuarios = $presentes === []
            ? collect()
            : User::role($presentes)->orderBy('id')->get();

        $migrados = 0;

        foreach ($usuarios as $usuario) {
            $antes = $usuario->getRoleNames();

            // Un admin con un rol antiguo encima perdería su acceso al
            // sincronizar: mejor no tocarlo y avisar.
            if ($antes->contains('admin')) {
                $this->warn("- {$usuario->email}: tiene rol admin, se omite.");

                continue;
            }

            $this->line("- {$usuario->email}: [{$antes->implode(', ')}] → usuario");

            if (! $this->option('dry-run')) {
                $usuario->syncRoles(['usuario']);
            }

            $migrados++;
        }

        $eliminados = 0;

        if (! $this->option('dry-run')) {
            foreach (Role::query()->whereIn('name', $this->legacy)->get() as $rol) {
                if ($rol->users()->exists()) {
                    $this->warn("- El rol {$rol->name} todavía tiene usuarios: no se elimina.");

                    continue;
                }

                $rol->delete();
                $eliminados++;
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->newLine();
        $this->info($this->option('dry-run')
            ? "{$migrados} usuario(s) cambiarían de rol (simulación, no se aplicó nada)."
            : "{$migrados} usuario(s) migrados al rol usuario; {$eliminados} rol(es) antiguo(s) eliminado(s).");

        return self::SUCCESS;
    }
}
