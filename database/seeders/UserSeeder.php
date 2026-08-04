<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Cuentas de desarrollo con contraseña conocida: jamás en producción.
        if (app()->environment('production')) {
            return;
        }

        $users = [
            ['name' => 'Administrador', 'email' => 'admin@ferreteria.test', 'role' => 'admin'],
            ['name' => 'Usuario Operaciones', 'email' => 'operaciones@ferreteria.test', 'role' => 'operaciones'],
            ['name' => 'Usuario Proveedores', 'email' => 'proveedores@ferreteria.test', 'role' => 'proveedores'],
            ['name' => 'Usuario Logística', 'email' => 'logistica@ferreteria.test', 'role' => 'logistica'],
            ['name' => 'Usuario Pagos', 'email' => 'pagos@ferreteria.test', 'role' => 'pagos'],
        ];

        foreach ($users as $data) {
            $user = User::query()->firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles([$data['role']]);
        }
    }
}
