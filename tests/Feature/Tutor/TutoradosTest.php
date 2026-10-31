<?php

namespace Tests\Feature\Tutor;

use App\Models\Activity;
use App\Models\Enrollment;
use App\Models\User;
use App\Support\ProgresoTutorado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TutoradosTest extends TestCase
{
    use RefreshDatabase;

    private User $tutor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Carbon::setTestNow('2026-11-25 12:00:00');
        foreach (['alumno', 'tutor', 'admin', 'responsable'] as $rol) {
            Role::findOrCreate($rol, 'web');
        }
        config(['aldea.horas_liberacion' => 20]);
        $this->tutor = User::factory()->create(['name' => 'Mtra. Laura Gómez'])->assignRole('tutor');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function alumno(array $attrs = [], ?User $tutor = null): User
    {
        return User::factory()->create(array_merge([
            'tutor_id' => ($tutor ?? $this->tutor)->id,
            'carrera' => 'Ingeniería en Software',
        ], $attrs))->assignRole('alumno');
    }

    private function actividad(array $attrs = []): Activity
    {
        return Activity::create(array_merge([
            'title' => 'Taller',
            'date' => '2026-11-24',
            'start_time' => '10:00',
            'end_time' => '12:00',
        ], $attrs));
    }

    private function inscribir(User $alumno, Activity $actividad, bool $asistio = false, string $status = 'registered'): Enrollment
    {
        return Enrollment::create([
            'user_id' => $alumno->id,
            'activity_id' => $actividad->id,
            'status' => $status,
            'attended' => $asistio,
        ]);
    }

    // ------------------------------------------------------------ Acceso

    public function test_el_tutor_entra_directo_a_sus_tutorados(): void
    {
        $this->actingAs($this->tutor)->get('/dashboard')->assertRedirect(route('tutor.tutorados'));
        $this->actingAs($this->tutor)->get(route('tutor.tutorados'))->assertOk()->assertSee('Supervisión y Seguimiento de Tutorados');
    }

    public function test_otros_roles_no_entran(): void
    {
        foreach (['alumno', 'admin', 'responsable'] as $rol) {
            $u = User::factory()->create()->assignRole($rol);
            $this->actingAs($u)->get(route('tutor.tutorados'))->assertForbidden();
        }
        auth()->logout();
        $this->get(route('tutor.tutorados'))->assertRedirect(route('login'));
    }

    public function test_solo_ve_a_sus_tutorados(): void
    {
        $mio = $this->alumno(['name' => 'Ana Mía']);
        $otroTutor = User::factory()->create()->assignRole('tutor');
        $ajeno = $this->alumno(['name' => 'Beto Ajeno'], $otroTutor);
        $sinTutor = User::factory()->create(['name' => 'Carla Sin Tutor'])->assignRole('alumno');

        $this->actingAs($this->tutor)->get(route('tutor.tutorados'))
            ->assertSee('Ana Mía')
            ->assertDontSee('Beto Ajeno')
            ->assertDontSee('Carla Sin Tutor');

        $this->actingAs($this->tutor)->get(route('tutor.tutorados.show', $mio))->assertOk()->assertSee('Ana Mía');
        $this->actingAs($this->tutor)->get(route('tutor.tutorados.show', $ajeno))->assertNotFound();
        $this->actingAs($this->tutor)->get(route('tutor.tutorados.show', $sinTutor))->assertNotFound();
    }

    public function test_es_solo_de_consulta(): void
    {
        $alumno = $this->alumno();

        $this->actingAs($this->tutor)->post(route('tutor.tutorados.show', $alumno))->assertStatus(405);
        $this->actingAs($this->tutor)->patch(route('tutor.tutorados.show', $alumno))->assertStatus(405);
        $this->actingAs($this->tutor)->delete(route('tutor.tutorados.show', $alumno))->assertStatus(405);
    }

    // ------------------------------------------------------------ Horas y estados

    public function test_solo_cuentan_inscripciones_activas_con_asistencia(): void
    {
        $alumno = $this->alumno();
        $this->inscribir($alumno, $this->actividad(['title' => 'Asistió', 'duration_hours' => 3]), asistio: true);
        $this->inscribir($alumno, $this->actividad(['title' => 'No asistió', 'duration_hours' => 5]));
        $this->inscribir($alumno, $this->actividad(['title' => 'Cancelada', 'duration_hours' => 7]), asistio: true, status: 'cancelled');
        // Sin duration_hours: usa la duración del horario (2 h)
        $this->inscribir($alumno, $this->actividad(['title' => 'Por horario']), asistio: true);

        $p = new ProgresoTutorado($alumno->load('enrollments.activity'));

        $this->assertSame(5.0, $p->horas);
        $this->assertSame(2, $p->asistencias);
        $this->assertSame(3, $p->inscripcionesActivas);
        $this->assertSame(25, $p->porcentaje);
        $this->assertSame(ProgresoTutorado::EN_PROGRESO, $p->estado);
    }

    public function test_clasifica_acreditado_en_progreso_y_pendiente(): void
    {
        $acreditado = $this->alumno(['name' => 'Ana Acreditada']);
        $this->inscribir($acreditado, $this->actividad(['duration_hours' => 20]), asistio: true);

        $progreso = $this->alumno(['name' => 'Pablo Progreso']);
        $this->inscribir($progreso, $this->actividad(['duration_hours' => 4]), asistio: true);

        $pendiente = $this->alumno(['name' => 'Pedro Pendiente']);
        $this->inscribir($pendiente, $this->actividad(['date' => '2026-11-26']));

        $estado = fn (User $u) => (new ProgresoTutorado($u->load('enrollments.activity')))->estado;
        $this->assertSame(ProgresoTutorado::ACREDITADO, $estado($acreditado));
        $this->assertSame(ProgresoTutorado::EN_PROGRESO, $estado($progreso));
        $this->assertSame(ProgresoTutorado::PENDIENTE, $estado($pendiente));
    }

    public function test_indicadores_del_panel(): void
    {
        $a = $this->alumno(['name' => 'Ana']);
        $b = $this->alumno(['name' => 'Beto']);
        $this->alumno(['name' => 'Ceci']); // sin inscripciones: pendiente
        $this->inscribir($a, $this->actividad(['duration_hours' => 20]), asistio: true);
        $this->inscribir($b, $this->actividad(['date' => '2026-11-26']));
        $this->inscribir($b, $this->actividad(['date' => '2026-11-26', 'start_time' => '13:00', 'end_time' => '14:00']));

        $res = $this->actingAs($this->tutor)->get(route('tutor.tutorados'))->assertOk();

        $this->assertSame(['asignados' => 3, 'asistencias' => 1, 'pendientes' => 2, 'inscripciones' => 3], $res->viewData('indicadores'));
        $this->assertSame(['acreditado' => 1, 'en_progreso' => 0, 'pendiente' => 2, 'todos' => 3], $res->viewData('conteos')->all());
    }

    // ------------------------------------------------------------ Búsqueda y filtros

    public function test_busca_por_nombre_o_matricula_y_filtra_por_estado(): void
    {
        $ana = $this->alumno(['name' => 'Ana Martínez', 'matricula' => '202300001']);
        $this->alumno(['name' => 'Beto López', 'matricula' => '202300002']);
        $this->inscribir($ana, $this->actividad(['duration_hours' => 20]), asistio: true);

        $nombres = fn ($res) => $res->viewData('tutorados')->map(fn ($p) => $p->alumno->name)->all();

        // Sin acentos también encuentra
        $this->assertSame(['Ana Martínez'], $nombres($this->actingAs($this->tutor)->get(route('tutor.tutorados', ['q' => 'martinez']))));
        $this->assertSame(['Beto López'], $nombres($this->actingAs($this->tutor)->get(route('tutor.tutorados', ['q' => '0002']))));
        $this->assertSame(['Ana Martínez'], $nombres($this->actingAs($this->tutor)->get(route('tutor.tutorados', ['estado' => 'acreditado']))));
        $this->assertSame(['Beto López'], $nombres($this->actingAs($this->tutor)->get(route('tutor.tutorados', ['estado' => 'pendiente']))));

        $this->actingAs($this->tutor)->get(route('tutor.tutorados', ['estado' => 'inventado']))->assertSessionHasErrors('estado');
    }

    // ------------------------------------------------------------ Resumen y detalle

    public function test_resumen_por_carrera_y_proximas_actividades(): void
    {
        $sw = $this->alumno(['carrera' => 'Ingeniería en Software']);
        $redes = $this->alumno(['carrera' => 'Ingeniería en Redes y Telecomunicaciones']);
        $this->inscribir($sw, $this->actividad(['duration_hours' => 4]), asistio: true);

        $foro = $this->actividad(['title' => 'Foro 2027', 'date' => '2026-11-26']);
        $this->inscribir($sw, $foro);
        $this->inscribir($redes, $foro);
        $this->inscribir($redes, $this->actividad(['title' => 'Pasada', 'date' => '2026-11-20']));

        $res = $this->actingAs($this->tutor)->get(route('tutor.tutorados'))->assertOk();

        $porCarrera = $res->viewData('porCarrera')->keyBy('carrera');
        $this->assertSame(4.0, $porCarrera['Ingeniería en Software']['horas']);
        $this->assertSame(20, $porCarrera['Ingeniería en Software']['porcentaje']);

        $proximas = $res->viewData('proximas');
        $this->assertCount(1, $proximas);
        $this->assertSame('Foro 2027', $proximas[0]['actividad']->title);
        $this->assertSame(2, $proximas[0]['tutorados']);
    }

    public function test_el_detalle_muestra_historial_y_horas(): void
    {
        $alumno = $this->alumno(['name' => 'Ana Martínez', 'matricula' => '202300001']);
        $this->inscribir($alumno, $this->actividad(['title' => 'Taller de Docker', 'duration_hours' => 3]), asistio: true);
        $this->inscribir($alumno, $this->actividad(['title' => 'Foro de egresados', 'date' => '2026-11-26']));

        $this->actingAs($this->tutor)->get(route('tutor.tutorados.show', $alumno))
            ->assertOk()
            ->assertSee('202300001')
            ->assertSee('Taller de Docker')
            ->assertSee('Asistencia validada')
            ->assertSee('+3 hrs')
            ->assertSee('Foro de egresados')
            ->assertSee('Próxima');
    }
}
