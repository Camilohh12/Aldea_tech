<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Crea un administrador con contraseña conocida: nunca en el servidor
        if (app()->isProduction()) {
            throw new \RuntimeException('DatabaseSeeder crea cuentas de prueba y no se corre en producción. En el servidor usa: php artisan db:seed --class=ProduccionSeeder --force');
        }

        // Crear Roles de Spatie
        $superAdmin = Role::create(['name' => 'super_admin']);
        $admin      = Role::create(['name' => 'admin']);
        $tutor      = Role::create(['name' => 'tutor']);
        $docente    = Role::create(['name' => 'docente']);
        $alumno     = Role::create(['name' => 'alumno']);
        $ponente    = Role::create(['name' => 'ponente']);

        // Crear usuario Administrador por defecto
        $adminUser = User::create([
            'name'     => 'Administrador Aldea',
            'email'    => 'admin@aldea.edu.mx',
            'password' => Hash::make('password123'),
        ]);
        $adminUser->assignRole($admin);

        // Crear tipos de actividad iniciales
        \DB::table('activity_types')->insert([
            ['name' => 'Conferencia', 'description' => 'Charla magistral'],
            ['name' => 'Taller', 'description' => 'Práctica estructurada'],
            ['name' => 'Curso', 'description' => 'Capacitación extendida'],
            ['name' => 'Foro', 'description' => 'Panel de discusión'],
        ]);
    }
}