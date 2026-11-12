<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Portada y panel admin con estilos compilados (sin el CDN de Tailwind)
 * y «Mi perfil» con el diseño morado y su menú.
 */
class EstilosYPerfilTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        foreach (['admin', 'super_admin', 'alumno'] as $rol) {
            Role::findOrCreate($rol, 'web');
        }
    }

    public function test_la_portada_y_el_panel_admin_no_cargan_tailwind_desde_internet(): void
    {
        $this->get('/')->assertOk()->assertDontSee('cdn.tailwindcss.com', false);

        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->get(route('admin.home'))
            ->assertOk()
            ->assertDontSee('cdn.tailwindcss.com', false);
    }

    public function test_mi_perfil_tiene_menu_para_volver_y_cerrar_sesion(): void
    {
        $alumno = User::factory()->create([
            'name' => 'Valeria Martínez Solís',
            'matricula' => '202308492',
            'carrera' => 'Ingeniería en Software',
            'cuatrimestre' => '7',
        ])->assignRole('alumno');

        $this->actingAs($alumno)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Mi panel')
            ->assertSee('href="'.route('dashboard').'"', false)
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('Datos de tu cuenta')
            ->assertSee('Cambiar contraseña')
            ->assertSee('Alumno')
            ->assertSee('Matrícula 202308492')
            ->assertSee('7° cuatrimestre');
    }

    public function test_mi_perfil_muestra_el_rol_del_administrador(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        $this->actingAs($admin)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Super administrador');
    }

    public function test_al_guardar_el_perfil_se_confirma_en_espanol(): void
    {
        $alumno = User::factory()->create([
            'name' => 'Valeria',
            'email' => 'valeria@upp.edu.mx',
            'carrera' => 'Ingeniería en Software',
            'cuatrimestre' => '7',
        ])->assignRole('alumno');

        $this->actingAs($alumno)
            ->followingRedirects()
            ->patch(route('profile.update'), [
                'name' => 'Valeria Martínez Solís',
                'email' => $alumno->email,
                'carrera' => $alumno->carrera,
                'cuatrimestre' => $alumno->cuatrimestre,
                'grupo' => 'STFW_09_01',
            ])
            ->assertOk()
            ->assertSee('Datos guardados.');

        $this->assertSame('Valeria Martínez Solís', $alumno->fresh()->name);
    }
}
