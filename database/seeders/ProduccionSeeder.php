<?php

namespace Database\Seeders;

use App\Models\ActivityType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Datos mínimos para el servidor de producción: roles, tipos de actividad y
 * la primera cuenta de Super Admin (pide correo y contraseña; no usa valores fijos).
 * Se puede correr varias veces sin duplicar nada.
 *
 * Uso: php artisan db:seed --class=ProduccionSeeder --force
 */
class ProduccionSeeder extends Seeder
{
    private const ROLES = ['super_admin', 'admin', 'tutor', 'docente', 'responsable', 'ponente', 'alumno'];

    private const TIPOS = [
        'Conferencia' => 'Charla magistral',
        'Taller' => 'Práctica estructurada',
        'Curso' => 'Capacitación extendida',
        'Foro' => 'Panel de discusión',
    ];

    public function run(): void
    {
        foreach (self::ROLES as $rol) {
            Role::findOrCreate($rol, 'web');
        }

        foreach (self::TIPOS as $nombre => $descripcion) {
            ActivityType::firstOrCreate(['name' => $nombre], ['description' => $descripcion]);
        }

        $this->command?->info('Roles y tipos de actividad listos.');

        if (User::role(['admin', 'super_admin'])->exists()) {
            $this->command?->info('Ya existe un administrador; no se crea otro.');

            return;
        }

        $this->crearSuperAdmin();
    }

    // Pide los datos en la terminal del servidor para no dejar contraseñas en el código
    private function crearSuperAdmin(): void
    {
        if (! $this->command) {
            return;
        }

        $nombre = trim((string) $this->command->ask('Nombre del administrador', 'Coordinación Aldea Tecnológica'));
        $correo = Str::lower(trim((string) $this->command->ask('Correo del administrador')));

        if (! filter_var($correo, FILTER_VALIDATE_EMAIL) || User::where('email', $correo)->exists()) {
            $this->command->error('Correo no válido o ya registrado. Vuelve a correr el seeder.');

            return;
        }

        $contrasena = (string) $this->command->secret('Contraseña (mínimo 12 caracteres)');

        if (mb_strlen($contrasena) < 12 || $contrasena !== (string) $this->command->secret('Repite la contraseña')) {
            $this->command->error('La contraseña es muy corta o no coincide. Vuelve a correr el seeder.');

            return;
        }

        $admin = User::create(['name' => $nombre, 'email' => $correo, 'password' => $contrasena]);
        $admin->markEmailAsVerified();
        $admin->assignRole('super_admin');

        $this->command->info("Super Admin creado: {$correo}");
    }
}
