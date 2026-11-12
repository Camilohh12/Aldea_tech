<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', $this->datos());

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $response->assertSessionHas('success');

        $user = \App\Models\User::where('matricula', '2023084921')->first();
        $this->assertTrue($user->hasRole('alumno'));
        $this->assertSame('Ingeniería en Software', $user->carrera);
    }

    public function test_puede_registrarse_como_irregular(): void
    {
        $this->post('/register', $this->datos(['cuatrimestre' => 'Irregular']))->assertSessionHasNoErrors();

        $user = \App\Models\User::where('matricula', '2023084921')->first();
        $this->assertSame('Irregular', $user->cuatrimestre);
        $this->assertSame('irregular', $user->estatus);
    }

    public function test_solo_acepta_correo_institucional(): void
    {
        // Solo @micorreo.upp.edu.mx: ni otros dominios ni otros subdominios de la UPP
        foreach (['valeria@gmail.com', 'valeria@upp.edu.mx', 'valeria@otro.upp.edu.mx',
            'valeria@micorreo.upp.edu.mx.com', 'valeria@x.micorreo.upp.edu.mx'] as $correo) {
            $this->post('/register', $this->datos(['email' => $correo]))
                ->assertSessionHasErrors(['email' => 'Usa tu correo institucional (@micorreo.upp.edu.mx).']);
        }
        $this->assertGuest();

        $this->post('/register', $this->datos(['email' => 'Valeria@MiCorreo.UPP.edu.mx']))
            ->assertSessionHasNoErrors();
        $this->assertSame('valeria@micorreo.upp.edu.mx', \App\Models\User::first()->email);
    }

    public function test_valida_matricula_y_aceptacion(): void
    {
        $this->post('/register', $this->datos(['matricula' => '12345']))->assertSessionHasErrors('matricula');
        $this->post('/register', $this->datos(['acepto' => null]))->assertSessionHasErrors('acepto');
        $this->assertGuest();
    }

    public function test_puede_iniciar_sesion_con_matricula(): void
    {
        $this->post('/register', $this->datos());
        auth()->logout();

        $this->post('/login', ['email' => '2023084921', 'password' => 'Password123'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();
    }

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'matricula' => '2023084921',
            'name' => 'Valeria Martínez Solís',
            'carrera' => 'Ingeniería en Software',
            'cuatrimestre' => 7,
            'grupo' => 'STFW_09_01',
            'email' => 'valeria.martinez@micorreo.upp.edu.mx',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'acepto' => '1',
        ], $cambios);
    }
}
