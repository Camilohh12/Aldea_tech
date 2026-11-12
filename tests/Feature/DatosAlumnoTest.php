<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Datos del alumno: matrícula de 10 dígitos y opción «Otro» en cuatrimestre,
 * con las mismas reglas en el registro y en «Mi perfil».
 */
class DatosAlumnoTest extends TestCase
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
            'cuatrimestre' => '7',
            'grupo' => 'STFW_09_01',
            'email' => 'valeria.martinez@micorreo.upp.edu.mx',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'acepto' => '1',
        ], $cambios);
    }

    private function alumno(array $datos = []): User
    {
        return User::factory()->create(array_merge([
            'name' => 'Ana Torres',
            'email' => 'ana.torres@upp.edu.mx',
            'matricula' => '2023000001',
            'carrera' => 'Ingeniería en Software',
            'cuatrimestre' => '5',
        ], $datos))->assignRole('alumno');
    }

    public function test_la_matricula_debe_tener_exactamente_10_digitos(): void
    {
        $mensaje = 'La matrícula debe tener 10 dígitos, sin espacios ni letras.';

        foreach (['202308492', '20230849211', '20230849AB', '2023 084921'] as $mala) {
            $this->post('/register', $this->datos(['matricula' => $mala]))
                ->assertSessionHasErrors(['matricula' => $mensaje]);
        }
        $this->assertGuest();

        $this->post('/register', $this->datos())->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['matricula' => '2023084921']);
    }

    public function test_el_registro_muestra_las_reglas_en_el_formulario(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('10 dígitos')
            ->assertSee('maxlength="10"', false)
            ->assertSee('pattern="[0-9]{10}"', false)
            ->assertSee('<option value="Otro"', false);
    }

    public function test_se_registra_con_cuatrimestre_otro(): void
    {
        $this->post('/register', $this->datos(['cuatrimestre' => 'Otro']))->assertSessionHasNoErrors();

        $alumno = User::where('matricula', '2023084921')->first();
        $this->assertSame('Otro', $alumno->cuatrimestre);
        $this->assertSame('regular', $alumno->estatus);
    }

    public function test_rechaza_cuatrimestres_y_nombres_invalidos(): void
    {
        $this->post('/register', $this->datos(['cuatrimestre' => '11']))
            ->assertSessionHasErrors(['cuatrimestre' => 'Selecciona un cuatrimestre de la lista.']);

        $this->post('/register', $this->datos(['name' => 'Valeria <script>']))
            ->assertSessionHasErrors('name');

        $this->assertGuest();
    }

    public function test_el_correo_se_guarda_en_minusculas(): void
    {
        $this->post('/register', $this->datos(['email' => 'Valeria.Martinez@MiCorreo.UPP.edu.mx']))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'valeria.martinez@micorreo.upp.edu.mx']);
    }

    public function test_el_alumno_edita_su_carrera_y_cuatrimestre_en_su_perfil(): void
    {
        $alumno = $this->alumno();

        $this->actingAs($alumno)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('name="cuatrimestre"', false)
            ->assertSee('<option value="Otro"', false);

        $this->actingAs($alumno)->patch(route('profile.update'), [
            'name' => 'Ana Torres',
            'email' => 'ana.torres@upp.edu.mx',
            'carrera' => 'Ingeniería en Redes y Telecomunicaciones',
            'cuatrimestre' => 'Otro',
        ])->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));

        $alumno->refresh();
        $this->assertSame('Otro', $alumno->cuatrimestre);
        $this->assertSame('Ingeniería en Redes y Telecomunicaciones', $alumno->carrera);
        $this->assertSame('regular', $alumno->estatus);

        // Y se puede volver a editar: al elegir «Irregular» cambia su situación
        $this->actingAs($alumno)->patch(route('profile.update'), [
            'name' => 'Ana Torres',
            'email' => 'ana.torres@upp.edu.mx',
            'carrera' => 'Ingeniería en Software',
            'cuatrimestre' => 'Irregular',
        ])->assertSessionHasNoErrors();

        $this->assertSame('irregular', $alumno->fresh()->estatus);

        $this->actingAs($alumno)->get(route('profile.edit'))->assertSee('Cuatrimestre: Irregular');
    }

    public function test_el_perfil_del_alumno_conserva_las_reglas_del_registro(): void
    {
        $alumno = $this->alumno();

        $this->actingAs($alumno)->from(route('profile.edit'))->patch(route('profile.update'), [
            'name' => 'Ana Torres',
            'email' => 'ana@gmail.com',
            'carrera' => 'Otra carrera',
            'cuatrimestre' => '12',
        ])->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors(['email', 'carrera', 'cuatrimestre']);

        $this->assertSame('ana.torres@upp.edu.mx', $alumno->fresh()->email);
    }

    public function test_una_cuenta_anterior_sin_correo_institucional_puede_guardar_su_perfil(): void
    {
        // Cuentas creadas por seeders o antes de la regla: guardan sin cambiar el correo
        $alumno = $this->alumno(['email' => 'alumno@aldea.edu.mx', 'matricula' => '202308492']);

        $this->actingAs($alumno)->patch(route('profile.update'), [
            'name' => 'Ana Torres',
            'email' => 'alumno@aldea.edu.mx',
            'carrera' => 'Ingeniería en Software',
            'cuatrimestre' => 'Otro',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Otro', $alumno->fresh()->cuatrimestre);

        // Pero si cambia el correo, el nuevo debe ser institucional
        $this->actingAs($alumno)->patch(route('profile.update'), [
            'name' => 'Ana Torres',
            'email' => 'ana@gmail.com',
            'carrera' => 'Ingeniería en Software',
            'cuatrimestre' => 'Otro',
        ])->assertSessionHasErrors(['email' => 'Usa tu correo institucional (@micorreo.upp.edu.mx).']);
    }

    public function test_el_perfil_de_otros_roles_no_pide_datos_academicos(): void
    {
        $admin = User::factory()->create(['email' => 'coordinacion@gmail.com'])->assignRole('admin');

        $this->actingAs($admin)->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee('name="cuatrimestre"', false);

        $this->actingAs($admin)->patch(route('profile.update'), [
            'name' => 'Coordinación',
            'email' => 'coordinacion@gmail.com',
        ])->assertSessionHasNoErrors();
    }
}
