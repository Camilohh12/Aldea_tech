<?php

namespace Tests\Feature\Alumno;

use App\Models\Activity;
use App\Models\Enrollment;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ModuloAlumnoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'alumno']);
        Role::create(['name' => 'admin']);
        Carbon::setTestNow('2026-11-10 08:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function alumno(): User
    {
        return User::factory()->create()->assignRole('alumno');
    }

    private function actividad(array $attrs = []): Activity
    {
        return Activity::create(array_merge([
            'title' => 'Taller de prueba',
            'date' => '2026-11-18',
            'start_time' => '11:00',
            'end_time' => '13:00',
            'capacity' => 10,
        ], $attrs));
    }

    // ------------------------------------------------------------ Acceso

    public function test_solo_alumnos_entran_al_modulo(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->get(route('alumno.catalogo'))->assertForbidden();
        $this->actingAs($this->alumno())->get(route('alumno.catalogo'))->assertOk();
    }

    public function test_el_dashboard_redirige_al_alumno_a_su_catalogo(): void
    {
        $this->actingAs($this->alumno())->get('/dashboard')->assertRedirect(route('alumno.catalogo'));
    }

    public function test_las_vistas_cargan(): void
    {
        $alumno = $this->alumno();
        $actividad = $this->actividad(['title' => 'IA generativa']);
        Enrollment::create(['user_id' => $alumno->id, 'activity_id' => $actividad->id]);

        $this->actingAs($alumno)->get(route('alumno.catalogo'))->assertOk()->assertSee('IA generativa')->assertSee('Inscrito');
        $this->actingAs($alumno)->get(route('alumno.agenda'))->assertOk()->assertSee('IA generativa');
        $this->actingAs($alumno)->get(route('alumno.escaner'))->assertOk()->assertSee('Registro manual');
    }

    public function test_el_escaner_no_expone_los_codigos_qr(): void
    {
        $alumno = $this->alumno();
        $actividad = $this->actividad(['title' => 'Docker']);
        Enrollment::create(['user_id' => $alumno->id, 'activity_id' => $actividad->id]);

        $this->actingAs($alumno)->get(route('alumno.escaner'))
            ->assertOk()
            ->assertSee(hash('sha256', $actividad->checkin_token))
            ->assertDontSee($actividad->checkin_token);
    }

    public function test_el_conflicto_de_horario_muestra_su_titulo(): void
    {
        $alumno = $this->alumno();
        $a = $this->actividad(['title' => 'Docker']);
        $b = $this->actividad(['title' => 'Laravel']);

        $this->actingAs($alumno)->post(route('alumno.inscripcion.store', $a));
        $this->actingAs($alumno)->post(route('alumno.inscripcion.store', $b))
            ->assertSessionHas('error_title', 'Conflicto de horario detectado');
    }

    // ------------------------------------------------------------ Inscripción

    public function test_el_alumno_se_inscribe(): void
    {
        $alumno = $this->alumno();
        $actividad = $this->actividad();

        $this->actingAs($alumno)
            ->post(route('alumno.inscripcion.store', $actividad))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $alumno->id,
            'activity_id' => $actividad->id,
            'status' => 'registered',
        ]);
    }

    public function test_no_se_puede_inscribir_si_el_cupo_esta_lleno(): void
    {
        $actividad = $this->actividad(['capacity' => 1]);
        $this->actingAs($this->alumno())->post(route('alumno.inscripcion.store', $actividad));

        $otro = $this->alumno();
        $this->actingAs($otro)
            ->post(route('alumno.inscripcion.store', $actividad))
            ->assertSessionHas('error', 'El cupo de esta actividad está lleno.');

        $this->assertSame(1, $actividad->enrollments()->count());
    }

    public function test_sin_cupo_propio_usa_el_del_aula(): void
    {
        $aula = Room::create(['name' => 'Lab 1', 'capacity' => 1]);
        $actividad = $this->actividad(['capacity' => null, 'room_id' => $aula->id]);

        $this->actingAs($this->alumno())->post(route('alumno.inscripcion.store', $actividad));
        $this->actingAs($this->alumno())
            ->post(route('alumno.inscripcion.store', $actividad))
            ->assertSessionHas('error', 'El cupo de esta actividad está lleno.');
    }

    public function test_no_se_inscribe_dos_veces(): void
    {
        $alumno = $this->alumno();
        $actividad = $this->actividad();

        $this->actingAs($alumno)->post(route('alumno.inscripcion.store', $actividad));
        $this->actingAs($alumno)
            ->post(route('alumno.inscripcion.store', $actividad))
            ->assertSessionHas('error', 'Ya estás inscrito en esta actividad.');
    }

    public function test_no_se_inscribe_a_actividades_que_se_cruzan(): void
    {
        $alumno = $this->alumno();
        $a = $this->actividad(['title' => 'Docker', 'start_time' => '11:00', 'end_time' => '13:00']);
        $b = $this->actividad(['title' => 'Laravel', 'start_time' => '12:00', 'end_time' => '14:00']);
        $c = $this->actividad(['title' => 'Git', 'start_time' => '13:00', 'end_time' => '14:00']);

        $this->actingAs($alumno)->post(route('alumno.inscripcion.store', $a))->assertSessionHas('success');
        $this->actingAs($alumno)->post(route('alumno.inscripcion.store', $b))->assertSessionHas('error');
        // Termina a las 13:00 y empieza a las 13:00: no se cruzan.
        $this->actingAs($alumno)->post(route('alumno.inscripcion.store', $c))->assertSessionHas('success');
    }

    public function test_no_se_inscribe_si_la_actividad_ya_empezo(): void
    {
        $actividad = $this->actividad(['date' => '2026-11-09']);

        $this->actingAs($this->alumno())
            ->post(route('alumno.inscripcion.store', $actividad))
            ->assertSessionHas('error');
    }

    public function test_cancelar_libera_el_lugar_y_se_puede_volver_a_inscribir(): void
    {
        $alumno = $this->alumno();
        $actividad = $this->actividad(['capacity' => 1]);

        $this->actingAs($alumno)->post(route('alumno.inscripcion.store', $actividad));
        $this->actingAs($alumno)->delete(route('alumno.inscripcion.destroy', $actividad))->assertSessionHas('success');

        $otro = $this->alumno();
        $this->actingAs($otro)->post(route('alumno.inscripcion.store', $actividad))->assertSessionHas('success');

        // El primero ya no alcanza lugar.
        $this->actingAs($alumno)->post(route('alumno.inscripcion.store', $actividad))->assertSessionHas('error');
    }

    // ------------------------------------------------------------ Asistencia por QR

    public function test_registra_asistencia_en_linea_con_el_qr_del_aula(): void
    {
        $alumno = $this->alumno();
        $actividad = $this->actividad(['checkin_enabled' => true]);
        Enrollment::create(['user_id' => $alumno->id, 'activity_id' => $actividad->id]);

        $this->actingAs($alumno)
            ->postJson(route('alumno.asistencias.store'), ['token' => $actividad->checkin_token])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertTrue($actividad->enrollments()->first()->attended);
    }

    public function test_rechaza_asistencia_si_el_pase_de_lista_no_esta_habilitado(): void
    {
        $alumno = $this->alumno();
        $actividad = $this->actividad(['checkin_enabled' => false]);
        Enrollment::create(['user_id' => $alumno->id, 'activity_id' => $actividad->id]);

        $this->actingAs($alumno)
            ->postJson(route('alumno.asistencias.store'), ['token' => $actividad->checkin_token])
            ->assertStatus(422);
    }

    public function test_rechaza_asistencia_si_no_esta_inscrito(): void
    {
        $actividad = $this->actividad(['checkin_enabled' => true]);

        $this->actingAs($this->alumno())
            ->postJson(route('alumno.asistencias.store'), ['token' => $actividad->checkin_token])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);
    }

    public function test_sincroniza_asistencias_guardadas_sin_conexion(): void
    {
        Carbon::setTestNow('2026-11-18 15:00:00'); // Ya terminó; se sincroniza después.

        $alumno = $this->alumno();
        $actividad = $this->actividad(); // switch apagado: en offline se valida por horario
        $otra = $this->actividad(['title' => 'Otra', 'date' => '2026-11-19']);
        Enrollment::create(['user_id' => $alumno->id, 'activity_id' => $actividad->id]);
        Enrollment::create(['user_id' => $alumno->id, 'activity_id' => $otra->id]);

        $res = $this->actingAs($alumno)->postJson(route('alumno.asistencias.sync'), [
            'items' => [
                // Escaneado a las 11:05 hora de México (17:05 UTC)
                ['id' => 'a', 'token' => $actividad->checkin_token, 'scanned_at' => '2026-11-18T17:05:00Z'],
                // Escaneo fuera del horario de su actividad
                ['id' => 'b', 'token' => $otra->checkin_token, 'scanned_at' => '2026-11-18T17:05:00Z'],
                ['id' => 'c', 'token' => 'no-existe', 'scanned_at' => '2026-11-18T17:05:00Z'],
            ],
        ])->assertOk();

        $resultados = collect($res->json('results'))->keyBy('id');
        $this->assertTrue($resultados['a']['ok']);
        $this->assertFalse($resultados['b']['ok']);
        $this->assertFalse($resultados['c']['ok']);

        $enrollment = $actividad->enrollments()->first();
        $this->assertTrue($enrollment->attended);
        $this->assertTrue($enrollment->synced_offline);
        $this->assertSame('11:05', $enrollment->checkin_time->format('H:i'));
    }
}
