<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Enrollment;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Asistencias del admin «Por grupo», igual que «Por taller». */
class AsistenciaPorGrupoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $tutor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        foreach (['admin', 'super_admin', 'alumno', 'tutor', 'docente', 'responsable'] as $rol) {
            Role::findOrCreate($rol, 'web');
        }

        $this->admin = User::factory()->create()->assignRole('admin');
        $this->tutor = User::factory()->create(['name' => 'Mtra. Laura Gómez'])->assignRole('tutor');
    }

    private int $matricula = 2023000001;

    private function alumno(string $nombre, ?string $grupo, string $cuatrimestre = '9', ?int $tutorId = null): User
    {
        $n = $this->matricula++;

        return User::factory()->create([
            'name' => $nombre,
            'email' => "alumno{$n}@upp.edu.mx",
            'matricula' => (string) $n,
            'carrera' => 'Ingeniería en Software',
            'cuatrimestre' => $cuatrimestre,
            'grupo' => $grupo,
            'tutor_id' => $tutorId ?? $this->tutor->id,
        ])->assignRole('alumno');
    }

    private function actividad(string $titulo, float $horas = 2): Activity
    {
        return Activity::create([
            'activity_type_id' => ActivityType::firstOrCreate(['name' => 'Taller'])->id,
            'room_id' => Room::firstOrCreate(['name' => 'Aula 1'], ['capacity' => 30])->id,
            'title' => $titulo,
            'date' => '2026-11-24', 'start_time' => '10:00', 'end_time' => '12:00',
            'capacity' => 30, 'duration_hours' => $horas,
        ]);
    }

    private function inscribir(User $alumno, Activity $actividad, bool $asistio): void
    {
        Enrollment::create(['user_id' => $alumno->id, 'activity_id' => $actividad->id, 'attended' => $asistio]);
    }

    /** Dos grupos de Software 9°, un irregular (cuenta en el grupo del tutor) y una cuenta sin grupo. */
    private function escenario(): array
    {
        $redes = $this->actividad('Taller de Redes', 2);
        $ia = $this->actividad('Conferencia de IA', 20);

        $ana = $this->alumno('Ana Torres', 'STFW_09_01');
        $beto = $this->alumno('Beto Ruiz', 'STFW_09_01');
        $carla = $this->alumno('Carla Díaz', 'STFW_09_02');
        $irregular = $this->alumno('Iván Irregular', null, 'Irregular');
        $sinGrupo = $this->alumno('Sofía Sin Grupo', null, '7');

        $this->inscribir($ana, $redes, true);
        $this->inscribir($ana, $ia, true);       // 22 h: liberó
        $this->inscribir($beto, $redes, false);
        $this->inscribir($irregular, $redes, true);
        $this->inscribir($carla, $ia, false);

        return compact('ana', 'beto', 'carla', 'irregular', 'sinGrupo');
    }

    public function test_la_vista_por_taller_tiene_la_pestana_por_grupo(): void
    {
        $this->actingAs($this->admin)->get(route('admin.attendances.index'))->assertOk()
            ->assertSee('Por taller')
            ->assertSee(route('admin.attendances.groups'), false);
    }

    public function test_lista_todos_los_grupos_con_su_participacion(): void
    {
        $this->escenario();

        $respuesta = $this->actingAs($this->admin)->get(route('admin.attendances.groups'))->assertOk();

        $respuesta->assertSeeInOrder(['STFW_09_01', 'STFW_09_02', 'Sin grupo'])
            ->assertSeeInOrder(['STFW_09_01', 'Software', '· Grupo 1 · 9°']);

        $grupos = collect($respuesta->viewData('grupos')->items())->keyBy('clave');

        // Ana, Beto y el irregular (su tutor tiene más tutorados en STFW_09_01)
        $this->assertSame(3, $grupos['STFW_09_01']->alumnos);
        $this->assertSame(1, $grupos['STFW_09_01']->porTutor);
        $this->assertSame(2, $grupos['STFW_09_01']->asistieron);
        $this->assertSame(4, $grupos['STFW_09_01']->inscripciones);
        $this->assertSame(3, $grupos['STFW_09_01']->asistencias);
        $this->assertSame(1, $grupos['STFW_09_01']->acreditados);
        $this->assertEquals(24.0, $grupos['STFW_09_01']->horas);

        $this->assertSame(1, $grupos['STFW_09_02']->alumnos);
        $this->assertSame(0, $grupos['STFW_09_02']->asistencias);
        $this->assertSame(1, $grupos['sin-grupo']->alumnos);

        $this->assertSame(1, $respuesta->viewData('stats')['sinGrupo']);
        $this->assertSame(2, $respuesta->viewData('stats')['grupos']);
    }

    public function test_filtra_por_codigo_carrera_y_cuatrimestre(): void
    {
        $this->escenario();
        $claves = fn ($respuesta) => collect($respuesta->viewData('grupos')->items())->pluck('clave')->all();

        $this->assertSame(['STFW_09_02'], $claves($this->actingAs($this->admin)->get(route('admin.attendances.groups', ['q' => 'STFW_09_02']))));
        $this->assertSame(['STFW_09_01', 'STFW_09_02'], $claves($this->actingAs($this->admin)->get(route('admin.attendances.groups', ['cuatrimestre' => '9']))));
        $this->assertSame([], $claves($this->actingAs($this->admin)->get(route('admin.attendances.groups', ['cuatrimestre' => '3']))));
        $this->assertSame([], $claves($this->actingAs($this->admin)->get(route('admin.attendances.groups', ['carrera' => 'Ingeniería en Tecnologías de la Información e Innovación Digital']))));

        // Filtros manipulados no rompen la página
        $this->actingAs($this->admin)->get(route('admin.attendances.groups', ['cuatrimestre' => ['x'], 'carrera' => "' OR 1=1 --"]))->assertOk();
    }

    public function test_el_detalle_del_grupo_lista_a_sus_alumnos(): void
    {
        $this->escenario();

        $this->actingAs($this->admin)->get(route('admin.attendances.group', 'STFW_09_01'))->assertOk()
            ->assertSee('Ingeniería en Software')
            ->assertSee('Grupo 1 · 9° cuatrimestre')
            ->assertSee('Mtra. Laura Gómez')
            ->assertSeeInOrder(['Ana Torres', 'Beto Ruiz', 'Iván Irregular'])
            ->assertSee('Irregular · grupo de su tutor')
            ->assertDontSee('Carla Díaz')
            ->assertSee('22 / 20 h')
            ->assertSee('Acreditado');

        // Búsqueda y pestaña por estado
        $this->actingAs($this->admin)->get(route('admin.attendances.group', ['grupo' => 'STFW_09_01', 'q' => 'beto']))
            ->assertSee('Beto Ruiz')->assertDontSee('Ana Torres');
        $this->actingAs($this->admin)->get(route('admin.attendances.group', ['grupo' => 'STFW_09_01', 'estado' => 'acreditado']))
            ->assertSee('Ana Torres')->assertDontSee('Beto Ruiz');
    }

    public function test_los_alumnos_sin_grupo_tienen_su_propia_lista(): void
    {
        $this->escenario();

        $this->actingAs($this->admin)->get(route('admin.attendances.group', 'sin-grupo'))->assertOk()
            ->assertSee('Sofía Sin Grupo')
            ->assertSee('Falta capturar su grupo')
            ->assertDontSee('Ana Torres');
    }

    public function test_un_grupo_que_no_existe_responde_404(): void
    {
        $this->escenario();

        $this->actingAs($this->admin)->get('/admin/asistencias/grupos/STFW_01_05')->assertNotFound();
        $this->actingAs($this->admin)->get('/admin/asistencias/grupos/STFW-01')->assertNotFound();
        $this->actingAs($this->admin)->get('/admin/asistencias/grupos/1%20OR%201=1')->assertNotFound();
    }

    public function test_descarga_la_lista_del_grupo_en_csv(): void
    {
        $this->escenario();
        $this->alumno('=HIPERVINCULO("x")', 'STFW_09_02');

        $csv = $this->actingAs($this->admin)->get(route('admin.attendances.group.export', 'STFW_09_01'))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=asistencia-grupo-STFW_09_01.csv')
            ->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Ana Torres', $csv);
        $this->assertStringContainsString('Taller de Redes; Conferencia de IA', $csv);
        $this->assertStringContainsString('Grupo de su tutor', $csv);
        $this->assertStringNotContainsString('Carla Díaz', $csv);

        // Un nombre con fórmula sale como texto, no se ejecuta en Excel
        $otro = $this->actingAs($this->admin)->get(route('admin.attendances.group.export', 'STFW_09_02'))->streamedContent();
        $this->assertStringContainsString('"\'=HIPERVINCULO(""x"")"', $otro);
    }

    public function test_descarga_el_resumen_de_todos_los_grupos(): void
    {
        $this->escenario();

        $csv = $this->actingAs($this->admin)->get(route('admin.attendances.groups.export'))->assertOk()->streamedContent();

        $this->assertStringContainsString('STFW_09_01,"Ingeniería en Software",9,1,3,1,2,4,3,24,1,"Mtra. Laura Gómez"', $csv);
        $this->assertStringContainsString('"Sin grupo"', $csv);
    }

    public function test_solo_el_admin_ve_las_asistencias_por_grupo(): void
    {
        $this->escenario();
        $alumno = User::role('alumno')->first();

        $this->actingAs($alumno)->get(route('admin.attendances.groups'))->assertForbidden();
        $this->actingAs($this->tutor)->get(route('admin.attendances.group', 'STFW_09_01'))->assertForbidden();
        $this->actingAs($this->tutor)->get(route('admin.attendances.groups.export'))->assertForbidden();
    }
}
