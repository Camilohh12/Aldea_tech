<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Grupo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Grupo del alumno: lo elige de la lista de la coordinación (TIID_01_02 =
 * TIID, 1er cuatrimestre, grupo 2) o «Irregular» / «Otro», que cuentan en el
 * grupo de su tutor.
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
            'grupo' => 'STFW_09_01',
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

    public function test_el_registro_muestra_la_lista_de_grupos(): void
    {
        $this->get('/register')->assertOk()
            ->assertSee('<select id="grupo" name="grupo"', false)
            ->assertSeeInOrder(['TIID_01_01', 'TIID_01_02', 'TIID_07_02', 'STFW_09_01', 'Irregular', 'Otro'], false)
            ->assertSee('cuentas en el grupo de tu tutor');
    }

    public function test_el_registro_guarda_el_grupo_elegido(): void
    {
        $this->post('/register', $this->datos(['grupo' => 'TIID_04_02']))->assertSessionHasNoErrors();
        $this->assertSame('TIID_04_02', $this->registrado()->grupo);
        $this->assertSame('regular', $this->registrado()->estatus);
    }

    public function test_el_grupo_es_obligatorio_y_de_la_lista(): void
    {
        $this->post('/register', $this->datos(['grupo' => '']))
            ->assertSessionHasErrors(['grupo' => 'Selecciona tu grupo.']);

        foreach (['1', 'TIID_99_99', 'tiid_01_01x', 'sftw_01_09'] as $malo) {
            $this->post('/register', $this->datos(['grupo' => $malo]))
                ->assertSessionHasErrors(['grupo' => 'Selecciona tu grupo de la lista.']);
        }

        $this->assertGuest();
    }

    public function test_irregular_u_otro_se_eligen_como_grupo(): void
    {
        $this->post('/register', $this->datos(['grupo' => 'Irregular']))->assertSessionHasNoErrors();
        $this->assertSame('Irregular', $this->registrado()->grupo);
        $this->assertSame('irregular', $this->registrado()->estatus);

        auth()->logout();
        $this->post('/register', $this->datos([
            'matricula' => '2023084922', 'email' => 'otro@micorreo.upp.edu.mx', 'grupo' => 'Otro',
        ]))->assertSessionHasNoErrors();
        $this->assertSame('Otro', User::where('matricula', '2023084922')->value('grupo'));
    }

    public function test_los_grupos_de_los_tutores_tambien_aparecen(): void
    {
        $tutor = User::factory()->create(['grupos_tutoria' => 'TIID_02_01, STFW_08_01'])->assignRole('tutor');

        $this->assertContains('TIID_02_01', Grupo::opciones());
        $this->assertSame($tutor->id, Grupo::tutorPorGrupo()['STFW_08_01']);

        $this->get('/register')->assertOk()->assertSee('STFW_08_01');
        $this->post('/register', $this->datos(['grupo' => 'STFW_08_01', 'tutor_id' => $tutor->id]))->assertSessionHasNoErrors();
        $this->assertSame('STFW_08_01', $this->registrado()->grupo);
    }

    public function test_el_perfil_muestra_y_cambia_el_grupo(): void
    {
        $alumno = $this->alumno(['grupo' => 'TIID_07_01']);

        $this->actingAs($alumno)->get(route('profile.edit'))->assertOk()
            ->assertSee('<select id="grupo" name="grupo"', false)
            ->assertSee('<option value="TIID_07_01" selected', false);

        $this->actingAs($alumno)->patch(route('profile.update'), [
            'name' => 'Ana Torres', 'email' => $alumno->email,
            'carrera' => 'Ingeniería en Software', 'cuatrimestre' => '7', 'grupo' => 'TIID_07_02',
        ])->assertSessionHasNoErrors();
        $this->assertSame('TIID_07_02', $alumno->fresh()->grupo);

        $this->actingAs($alumno)->patch(route('profile.update'), [
            'name' => 'Ana Torres', 'email' => $alumno->email,
            'carrera' => 'Ingeniería en Software', 'cuatrimestre' => '7', 'grupo' => 'Irregular',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Irregular', $alumno->fresh()->grupo);
        $this->assertSame('irregular', $alumno->fresh()->estatus);
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
            'carrera' => 'Ingeniería en Software', 'cuatrimestre' => '7', 'grupo' => 'TIID_07_01',
        ])->assertSessionHasNoErrors();
        $this->assertSame('TIID_07_01', $alumno->fresh()->grupo);
    }

    public function test_el_perfil_de_otros_roles_no_pide_grupo(): void
    {
        $tutor = User::factory()->create(['email' => 'tutor@upp.edu.mx'])->assignRole('tutor');

        $this->actingAs($tutor)->get(route('profile.edit'))->assertOk()->assertDontSee('name="grupo"', false);
        $this->actingAs($tutor)->patch(route('profile.update'), ['name' => 'Tutor', 'email' => $tutor->email])
            ->assertSessionHasNoErrors();
    }

    public function test_partes_del_codigo(): void
    {
        $this->assertSame(['carrera' => 'Ingeniería en Software', 'cuatrimestre' => 9, 'numero' => 1], Grupo::partes('STFW_09_01'));
        $this->assertSame('Grupo 2 · 1° cuatrimestre', Grupo::descripcion('TIID_01_02'));
        $this->assertNull(Grupo::partes('XYZ_01_01'));   // prefijo que no es de ninguna carrera
        $this->assertNull(Grupo::partes('STFW-09-01'));
        $this->assertTrue(Grupo::esCodigo('TIID_01_02'));
        $this->assertFalse(Grupo::esCodigo('Irregular'));
    }

    public function test_irregular_u_otro_cuentan_en_el_grupo_de_su_tutor(): void
    {
        $tutor = User::factory()->create()->assignRole('tutor');
        $otroTutor = User::factory()->create(['grupos_tutoria' => 'TIID_06_01'])->assignRole('tutor');
        $tutorSinGrupo = User::factory()->create()->assignRole('tutor');

        $a = $this->alumno(['matricula' => '2023000001', 'email' => 'a@upp.edu.mx', 'grupo' => 'STFW_09_01', 'tutor_id' => $tutor->id]);
        $b = $this->alumno(['matricula' => '2023000002', 'email' => 'b@upp.edu.mx', 'grupo' => 'STFW_09_01', 'tutor_id' => $tutor->id]);
        $c = $this->alumno(['matricula' => '2023000003', 'email' => 'c@upp.edu.mx', 'grupo' => 'STFW_09_02', 'tutor_id' => $tutor->id]);
        $irregular = $this->alumno(['matricula' => '2023000004', 'email' => 'd@upp.edu.mx', 'grupo' => 'Irregular', 'tutor_id' => $tutor->id]);
        $otro = $this->alumno(['matricula' => '2023000005', 'email' => 'e@upp.edu.mx', 'grupo' => 'Otro', 'tutor_id' => $otroTutor->id]);
        $sinGrupoDelTutor = $this->alumno(['matricula' => '2023000006', 'email' => 'f@upp.edu.mx', 'grupo' => 'Otro', 'tutor_id' => $tutorSinGrupo->id]);
        $regularSinCapturar = $this->alumno(['matricula' => '2023000007', 'email' => 'g@upp.edu.mx', 'tutor_id' => $tutor->id]);
        $cuentaAnterior = $this->alumno(['matricula' => '2023000008', 'email' => 'h@upp.edu.mx', 'cuatrimestre' => 'Irregular', 'tutor_id' => $tutor->id]);

        $grupos = Grupo::resolver(User::role('alumno')->get());

        $this->assertSame(['grupo' => 'STFW_09_01', 'por_tutor' => false], $grupos[$a->id]);
        $this->assertSame('STFW_09_02', $grupos[$c->id]['grupo']);
        // El grupo que más se repite entre los tutorados de su tutor
        $this->assertSame(['grupo' => 'STFW_09_01', 'por_tutor' => true], $grupos[$irregular->id]);
        // Sin tutorados con grupo: el grupo que atiende su tutor
        $this->assertSame(['grupo' => 'TIID_06_01', 'por_tutor' => true], $grupos[$otro->id]);
        // Su tutor no tiene grupo de ninguna forma
        $this->assertSame(['grupo' => null, 'por_tutor' => false], $grupos[$sinGrupoDelTutor->id]);
        // Un regular sin capturar no se adivina: debe capturarlo
        $this->assertSame(['grupo' => null, 'por_tutor' => false], $grupos[$regularSinCapturar->id]);
        // Cuentas anteriores con cuatrimestre «Irregular» y sin grupo siguen contando con su tutor
        $this->assertSame(['grupo' => 'STFW_09_01', 'por_tutor' => true], $grupos[$cuentaAnterior->id]);
        $this->assertSame('STFW_09_01', $grupos[$b->id]['grupo']);
    }
}
