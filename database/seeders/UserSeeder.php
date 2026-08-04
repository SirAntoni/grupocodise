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
            ['name' => 'Usuario de Prueba', 'email' => 'usuario@ferreteria.test', 'role' => 'usuario'],
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
