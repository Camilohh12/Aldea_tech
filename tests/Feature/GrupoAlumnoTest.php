<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Grupo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Grupo del alumno: escribe solo el número y se guarda el código
 * prefijo_grupo_cuatrimestre (sftw_01_09). «Irregular» y «Otro» quedan en el grupo de su tutor.
 */
class GrupoAlumnoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        foreach (['admin', 'super_admin', 'alumno', 'tutor', 'docente', 'responsable'] as $rol) {
            Role::findOrCreate($rol, 'web');
        }
    }

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'matricula' => '2023084921',
            'name' => 'Valeria Martínez Solís',
            'carrera' => 'Ingeniería en Software',
            'cuatrimestre' => '9',
            'grupo' => '1',
            'email' => 'valeria.martinez@micorreo.upp.edu.mx',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'acepto' => '1',
        ], $cambios);
    }

    private function registrado(): ?User
    {
        return User::where('matricula', '2023084921')->first();
    }

    private function alumno(array $datos = []): User
    {
        return User::factory()->create(array_merge([
            'name' => 'Ana Torres',
            'email' => 'ana.torres@upp.edu.mx',
            'matricula' => '2023000001',
            'carrera' => 'Ingeniería en Software',
            'cuatrimestre' => '7',
        ], $datos))->assignRole('alumno');
    }

    public function test_el_registro_pide_el_grupo_con_un_ejemplo(): void
    {
        $this->get('/register')->assertOk()
            ->assertSee('name="grupo"', false)
            ->assertSee('placeholder="Ej. 1"', false)
            ->assertSee('quedas en el grupo de tu tutor');
    }

    public function test_el_registro_guarda_el_codigo_completo(): void
    {
        $this->post('/register', $this->datos())->assertSessionHasNoErrors();
        $this->assertSame('sftw_01_09', $this->registrado()->grupo);
    }

    public function test_el_codigo_usa_el_prefijo_de_cada_carrera(): void
    {
        $this->post('/register', $this->datos([
            'carrera' => 'Ingeniería en Tecnologías de la Información e Innovación Digital',
            'cuatrimestre' => '1',
            'grupo' => '02', // también con cero adelante
        ]))->assertSessionHasNoErrors();

        $this->assertSame('tiid_02_01', $this->registrado()->grupo);
        $this->assertSame('redte_03_10', Grupo::codigo('Ingeniería en Redes y Telecomunicaciones', '10', 3));
    }

    public function test_el_grupo_es_obligatorio_y_solo_un_numero(): void
    {
        $this->post('/register', $this->datos(['grupo' => '']))
            ->assertSessionHasErrors(['grupo' => 'Escribe el número de tu grupo, por ejemplo 1.']);

        foreach (['A', '1A', '0', '100', '-1', 'sftw_01_09'] as $malo) {
            $this->post('/register', $this->datos(['grupo' => $malo]))
                ->assertSessionHasErrors(['grupo' => 'Escribe solo el número de tu grupo (del 1 al 99), por ejemplo 1.']);
        }

        $this->assertGuest();
    }

    public function test_irregular_u_otro_no_capturan_grupo(): void
    {
        // Aunque llegue un número, no se guarda: quedan en el grupo de su tutor
        $this->post('/register', $this->datos(['cuatrimestre' => 'Irregular', 'grupo' => '3']))->assertSessionHasNoErrors();
        $this->assertNull($this->registrado()->grupo);

        auth()->logout();
        $this->post('/register', $this->datos([
            'matricula' => '2023084922', 'email' => 'otro@micorreo.upp.edu.mx', 'cuatrimestre' => 'Otro', 'grupo' => null,
        ]))->assertSessionHasNoErrors();
        $this->assertNull(User::where('matricula', '2023084922')->value('grupo'));
    }

    public function test_el_perfil_muestra_el_numero_y_actualiza_el_codigo(): void
    {
        $alumno = $this->alumno(['grupo' => 'sftw_02_07']);

        $this->actingAs($alumno)->get(route('profile.edit'))->assertOk()
            ->assertSee('name="grupo"', false)
            ->assertSee('value="2"', false);

        // Pasa a 8°: el código se vuelve a armar
        $this->actingAs($alumno)->patch(route('profile.update'), [
            'name' => 'Ana Torres', 'email' => $alumno->email,
            'carrera' => 'Ingeniería en Software', 'cuatrimestre' => '8', 'grupo' => '2',
        ])->assertSessionHasNoErrors();
        $this->assertSame('sftw_02_08', $alumno->fresh()->grupo);

        // Si cambia a irregular deja de tener grupo propio
        $this->actingAs($alumno)->patch(route('profile.update'), [
            'name' => 'Ana Torres', 'email' => $alumno->email,
            'carrera' => 'Ingeniería en Software', 'cuatrimestre' => 'Irregular',
        ])->assertSessionHasNoErrors();
        $this->assertNull($alumno->fresh()->grupo);
    }

    public function test_una_cuenta_sin_grupo_lo_captura_al_guardar_su_perfil(): void
    {
        $alumno = $this->alumno(); // registrada antes de pedir el grupo

        $this->actingAs($alumno)->patch(route('profile.update'), [
            'name' => 'Ana Torres', 'email' => $alumno->email,
            'carrera' => 'Ingeniería en Software', 'cuatrimestre' => '7',
        ])->assertSessionHasErrors('grupo');

        $this->actingAs($alumno)->patch(route('profile.update'), [
            'name' => 'Ana Torres', 'email' => $alumno->email,
            'carrera' => 'Ingeniería en Software', 'cuatrimestre' => '7', 'grupo' => '3',
        ])->assertSessionHasNoErrors();
        $this->assertSame('sftw_03_07', $alumno->fresh()->grupo);
    }

    public function test_el_perfil_de_otros_roles_no_pide_grupo(): void
    {
        $tutor = User::factory()->create(['email' => 'tutor@upp.edu.mx'])->assignRole('tutor');

        $this->actingAs($tutor)->get(route('profile.edit'))->assertOk()->assertDontSee('name="grupo"', false);
        $this->actingAs($tutor)->patch(route('profile.update'), ['name' => 'Tutor', 'email' => $tutor->email])
            ->assertSessionHasNoErrors();
    }

    public function test_partes_y_numero_del_codigo(): void
    {
        $this->assertSame(['carrera' => 'Ingeniería en Software', 'numero' => 1, 'cuatrimestre' => 9], Grupo::partes('sftw_01_09'));
        $this->assertSame(2, Grupo::numero('tiid_02_01'));
        $this->assertNull(Grupo::partes('xyz_01_01'));   // prefijo que no es de ninguna carrera
        $this->assertNull(Grupo::partes('sftw-01-09'));
        $this->assertNull(Grupo::codigo('Ingeniería en Software', 'Irregular', 1));
        $this->assertNull(Grupo::codigo('Otra carrera', '3', 1));
    }

    public function test_el_irregular_queda_en_el_grupo_de_su_tutor(): void
    {
        $tutor = User::factory()->create()->assignRole('tutor');
        $otroTutor = User::factory()->create()->assignRole('tutor');

        $a = $this->alumno(['matricula' => '2023000001', 'email' => 'a@upp.edu.mx', 'grupo' => 'sftw_01_09', 'tutor_id' => $tutor->id]);
        $b = $this->alumno(['matricula' => '2023000002', 'email' => 'b@upp.edu.mx', 'grupo' => 'sftw_01_09', 'tutor_id' => $tutor->id]);
        $c = $this->alumno(['matricula' => '2023000003', 'email' => 'c@upp.edu.mx', 'grupo' => 'sftw_02_09', 'tutor_id' => $tutor->id]);
        $irregular = $this->alumno(['matricula' => '2023000004', 'email' => 'd@upp.edu.mx', 'cuatrimestre' => 'Irregular', 'tutor_id' => $tutor->id]);
        $irregularSinGrupo = $this->alumno(['matricula' => '2023000005', 'email' => 'e@upp.edu.mx', 'cuatrimestre' => 'Otro', 'tutor_id' => $otroTutor->id]);
        $regularSinCapturar = $this->alumno(['matricula' => '2023000006', 'email' => 'f@upp.edu.mx', 'tutor_id' => $tutor->id]);
        $sinCuatrimestre = $this->alumno(['matricula' => '2023000007', 'email' => 'g@upp.edu.mx', 'cuatrimestre' => null, 'tutor_id' => $tutor->id]);

        $grupos = Grupo::resolver(User::role('alumno')->get());

        $this->assertSame(['grupo' => 'sftw_01_09', 'por_tutor' => false], $grupos[$a->id]);
        $this->assertSame('sftw_02_09', $grupos[$c->id]['grupo']);
        // El grupo que más se repite entre los tutorados de su tutor
        $this->assertSame(['grupo' => 'sftw_01_09', 'por_tutor' => true], $grupos[$irregular->id]);
        // Su tutor no tiene tutorados con grupo
        $this->assertSame(['grupo' => null, 'por_tutor' => false], $grupos[$irregularSinGrupo->id]);
        // Un regular sin capturar no se adivina: debe capturarlo
        $this->assertSame(['grupo' => null, 'por_tutor' => false], $grupos[$regularSinCapturar->id]);
        // Sin cuatrimestre (cuentas anteriores) tampoco: no es «Irregular» ni «Otro»
        $this->assertSame(['grupo' => null, 'por_tutor' => false], $grupos[$sinCuatrimestre->id]);
        $this->assertSame('sftw_01_09', $grupos[$b->id]['grupo']);
    }
}
