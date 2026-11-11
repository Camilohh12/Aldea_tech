<?php

namespace Tests\Feature;

use App\Models\ActivityType;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Database\Seeders\ProduccionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Preparación para producción: mensajes en español, seeder de producción y HTTPS.
 */
class ProduccionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TrustProxies::flushState();

        parent::tearDown();
    }

    public function test_los_errores_del_registro_salen_en_espanol(): void
    {
        $this->post('/register', [
            'matricula' => '2023084921',
            'name' => 'Valeria Martínez Solís',
            'carrera' => 'Ingeniería en Software',
            'cuatrimestre' => 7,
            'email' => 'valeria.martinez@upp.edu.mx',
            'password' => 'corta',
            'password_confirmation' => 'otra',
            'acepto' => '1',
        ])->assertSessionHasErrors([
            'password' => 'La confirmación de contraseña no coincide.',
        ]);
    }

    public function test_la_recuperacion_de_contrasena_y_las_paginas_de_error_salen_en_espanol(): void
    {
        $this->post('/forgot-password', ['email' => 'nadie@upp.edu.mx'])
            ->assertSessionHasErrors(['email' => 'No encontramos ninguna cuenta con ese correo.']);

        $this->get('/forgot-password')->assertSee('Recuperar contraseña');

        $this->get('/no-existe')->assertNotFound()->assertSee('Página no encontrada');
    }

    public function test_el_seeder_de_produccion_crea_roles_tipos_y_super_admin(): void
    {
        $this->artisan('db:seed', ['--class' => ProduccionSeeder::class])
            ->expectsQuestion('Nombre del administrador', 'Coordinación Aldea')
            ->expectsQuestion('Correo del administrador', 'Coordinacion.Aldea@upp.edu.mx')
            ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'Una-Clave-Muy-Larga-2026')
            ->expectsQuestion('Repite la contraseña', 'Una-Clave-Muy-Larga-2026')
            ->expectsOutputToContain('Super Admin creado: coordinacion.aldea@upp.edu.mx')
            ->assertSuccessful();

        foreach (['super_admin', 'admin', 'tutor', 'docente', 'responsable', 'ponente', 'alumno'] as $rol) {
            $this->assertTrue(Role::where('name', $rol)->exists(), "Falta el rol {$rol}");
        }
        $this->assertSame(4, ActivityType::count());

        $admin = User::where('email', 'coordinacion.aldea@upp.edu.mx')->firstOrFail();
        $this->assertTrue($admin->hasRole('super_admin'));
        $this->assertTrue($admin->hasVerifiedEmail());
        $this->assertTrue(Hash::check('Una-Clave-Muy-Larga-2026', $admin->password));

        // Correrlo otra vez no duplica nada ni pide otro administrador
        $this->artisan('db:seed', ['--class' => ProduccionSeeder::class])
            ->expectsOutputToContain('Ya existe un administrador')
            ->assertSuccessful();

        $this->assertSame(7, Role::count());
        $this->assertSame(4, ActivityType::count());
        $this->assertSame(1, User::count());
    }

    public function test_el_seeder_rechaza_contrasenas_cortas(): void
    {
        $this->artisan('db:seed', ['--class' => ProduccionSeeder::class])
            ->expectsQuestion('Nombre del administrador', 'Coordinación Aldea')
            ->expectsQuestion('Correo del administrador', 'coordinacion@upp.edu.mx')
            ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'corta')
            ->expectsOutputToContain('La contraseña es muy corta')
            ->assertSuccessful();

        $this->assertSame(0, User::count());
    }

    public function test_con_app_url_https_los_enlaces_salen_con_https(): void
    {
        config(['app.url' => 'https://aldea.upp.edu.mx']);
        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('https://', route('login'));
    }

    public function test_sin_https_los_enlaces_no_se_fuerzan(): void
    {
        config(['app.url' => 'http://localhost']);
        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('http://', route('login'));
    }

    public function test_los_proxies_de_confianza_se_leen_de_la_configuracion(): void
    {
        config(['app.trusted_proxies' => '*']);
        (new AppServiceProvider($this->app))->boot();

        Route::get('/prueba-esquema', fn () => request()->isSecure() ? 'seguro' : 'inseguro');

        $this->get('/prueba-esquema', ['X-Forwarded-Proto' => 'https'])->assertContent('seguro');
    }
}
