<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\TutoresGruposSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tutores dados de alta por grupo (Excel GRUPOS_TIID_Y_SFTW) y la
 * nomenclatura del grupo al principio del nombre.
 */
class TutoresGruposTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        foreach (['admin', 'super_admin', 'tutor', 'alumno', 'docente', 'responsable', 'ponente'] as $rol) {
            Role::findOrCreate($rol, 'web');
        }
        $this->admin = User::factory()->create()->assignRole('admin');
    }

    public function test_el_seeder_da_de_alta_a_los_tutores_con_sus_grupos(): void
    {
        $this->seed(TutoresGruposSeeder::class);

        // 10 grupos, pero Mayra Fabiola tiene dos
        $this->assertSame(9, User::role('tutor')->count());

        $mayra = User::where('name', 'Mayra Fabiola Gonzalez Peralta')->first();
        $this->assertSame('TIID_01_02, TIID_04_01', $mayra->grupos_tutoria);
        $this->assertSame('Ingeniería en Tecnologías de la Información e Innovación Digital', $mayra->carrera);
        $this->assertTrue($mayra->correoPendiente());

        $alicia = User::where('name', 'Alicia Ortiz Montes')->first();
        $this->assertSame('STFW_09_01', $alicia->grupos_tutoria);
        $this->assertSame('Ingeniería en Software', $alicia->carrera);

        // Los dobles espacios del Excel no pasan al nombre
        $this->assertTrue(User::where('name', 'Jazmin Rodriguez Flores')->exists());
    }

    public function test_correr_el_seeder_dos_veces_no_duplica_tutores(): void
    {
        $this->seed(TutoresGruposSeeder::class);
        $this->seed(TutoresGruposSeeder::class);

        $this->assertSame(9, User::role('tutor')->count());
        $this->assertSame(9, User::role('tutor')->distinct()->count('email'));
    }

    public function test_los_tutores_nuevos_no_tienen_acceso_todavia(): void
    {
        $this->seed(TutoresGruposSeeder::class);
        $yessica = User::where('name', 'Yessica Ruiz Salazar')->first();

        $this->post(route('login'), ['email' => $yessica->email, 'password' => 'password'])
            ->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_el_registro_muestra_el_grupo_al_principio_del_tutor(): void
    {
        $this->seed(TutoresGruposSeeder::class);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('TIID_01_01 · Yessica Ruiz Salazar')
            ->assertSee('TIID_01_02, TIID_04_01 · Mayra Fabiola Gonzalez Peralta')
            ->assertSeeInOrder(['TIID_01_01 · Yessica', 'TIID_01_02, TIID_04_01 · Mayra', 'TIID_01_03 · Diana']);
    }

    public function test_la_lista_de_tutores_empieza_por_el_grupo(): void
    {
        $this->seed(TutoresGruposSeeder::class);

        $this->actingAs($this->admin)
            ->get(route('admin.tutors.index'))
            ->assertOk()
            ->assertSeeInOrder(['Grupo', 'Tutor', 'TIID_01_01', 'Yessica Ruiz Salazar'])
            ->assertSee('Sin acceso · falta su correo');

        $this->actingAs($this->admin)
            ->get(route('admin.tutors.index', ['q' => 'STFW_09']))
            ->assertSee('Alicia Ortiz Montes')
            ->assertDontSee('Yessica Ruiz Salazar');
    }

    public function test_el_admin_captura_los_grupos_y_se_ordenan(): void
    {
        $tutor = User::factory()->create(['name' => 'Mtra. Laura Gómez'])->assignRole('tutor');

        $this->actingAs($this->admin)->put(route('admin.tutors.update', $tutor), [
            'name' => 'Mtra. Laura Gómez',
            'email' => $tutor->email,
            'grupos_tutoria' => 'tiid_4_1,  TIID_01_02, tiid_04_01',
        ])->assertSessionHasNoErrors();

        $this->assertSame('TIID_01_02, TIID_04_01', $tutor->fresh()->grupos_tutoria);

        $this->actingAs($this->admin)->put(route('admin.tutors.update', $tutor), [
            'name' => 'Mtra. Laura Gómez',
            'email' => $tutor->email,
            'grupos_tutoria' => 'grupo uno',
        ])->assertSessionHasErrors('grupos_tutoria');
    }

    // ------------------------------------------------------------ Asignación automática

    private function datosRegistro(array $cambios = []): array
    {
        return array_merge([
            'matricula' => '2023084921',
            'name' => 'Valeria Martínez Solís',
            'carrera' => 'Ingeniería en Tecnologías de la Información e Innovación Digital',
            'cuatrimestre' => '1',
            'grupo' => 'TIID_01_01',
            'email' => 'valeria.martinez@micorreo.upp.edu.mx',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'acepto' => '1',
        ], $cambios);
    }

    public function test_el_alumno_queda_solo_en_la_lista_del_tutor_de_su_grupo(): void
    {
        $this->seed(TutoresGruposSeeder::class);
        $yessica = User::where('name', 'Yessica Ruiz Salazar')->first();
        $otra = User::where('name', 'Alicia Ortiz Montes')->first();

        // No elige tutor; y si manda otro, gana el de su grupo
        $this->post('/register', $this->datosRegistro(['tutor_id' => $otra->id]))->assertSessionHasNoErrors();

        $alumno = User::where('matricula', '2023084921')->first();
        $this->assertSame($yessica->id, $alumno->tutor_id);

        // El tutor ya lo ve en su panel
        $this->actingAs($yessica)->get(route('tutor.tutorados'))->assertOk()->assertSee('Valeria Martínez Solís');
    }

    public function test_irregular_u_otro_si_eligen_tutor(): void
    {
        $this->seed(TutoresGruposSeeder::class);
        $erika = User::where('name', 'Erika Verenice Ceron Cornejo')->first();

        $this->post('/register', $this->datosRegistro(['grupo' => 'Irregular']))->assertSessionHasErrors('tutor_id');
        $this->assertGuest();

        $this->post('/register', $this->datosRegistro(['grupo' => 'Irregular', 'tutor_id' => $erika->id]))->assertSessionHasNoErrors();
        $this->assertSame($erika->id, User::where('matricula', '2023084921')->value('tutor_id'));
    }

    public function test_al_cambiar_de_grupo_en_el_perfil_cambia_de_tutor(): void
    {
        $this->seed(TutoresGruposSeeder::class);
        $yessica = User::where('name', 'Yessica Ruiz Salazar')->first();
        $diana = User::where('name', 'Diana Xochitl Ruano Vargas')->first();
        $alumno = User::factory()->create([
            'email' => 'ana@micorreo.upp.edu.mx', 'carrera' => 'Ingeniería en Software', 'cuatrimestre' => '1',
            'grupo' => 'TIID_01_01', 'tutor_id' => $yessica->id,
        ])->assignRole('alumno');

        $this->actingAs($alumno)->patch(route('profile.update'), [
            'name' => 'Ana Torres', 'email' => $alumno->email,
            'carrera' => 'Ingeniería en Software', 'cuatrimestre' => '1', 'grupo' => 'TIID_01_03',
        ])->assertSessionHasNoErrors();

        $this->assertSame($diana->id, $alumno->fresh()->tutor_id);
    }

    public function test_los_alumnos_ya_registrados_pasan_al_tutor_cuando_recibe_su_grupo(): void
    {
        $alumno = User::factory()->create(['grupo' => 'TIID_02_01'])->assignRole('alumno');
        $deOtroGrupo = User::factory()->create(['grupo' => 'TIID_01_01'])->assignRole('alumno');
        $tutor = User::factory()->create(['name' => 'Mtra. Laura Gómez'])->assignRole('tutor');

        $this->actingAs($this->admin)->put(route('admin.tutors.update', $tutor), [
            'name' => 'Mtra. Laura Gómez', 'email' => $tutor->email, 'grupos_tutoria' => 'TIID_02_01',
        ])->assertSessionHas('success', 'Tutor actualizado correctamente. Alumnos de sus grupos asignados: 1.');

        $this->assertSame($tutor->id, $alumno->fresh()->tutor_id);
        $this->assertNull($deOtroGrupo->fresh()->tutor_id);

        // Y el seeder hace lo mismo con los grupos del Excel
        $this->seed(TutoresGruposSeeder::class);
        $this->assertSame(User::where('name', 'Yessica Ruiz Salazar')->value('id'), $deOtroGrupo->fresh()->tutor_id);
    }

    public function test_ya_no_existe_la_pantalla_de_asignar_alumnos(): void
    {
        $tutor = User::factory()->create()->assignRole('tutor');

        $this->actingAs($this->admin)->get(route('admin.tutors.index'))->assertOk()->assertDontSee('Asignar alumnos');
        $this->actingAs($this->admin)->get("/admin/tutors/{$tutor->id}/alumnos")->assertNotFound();
    }
}
