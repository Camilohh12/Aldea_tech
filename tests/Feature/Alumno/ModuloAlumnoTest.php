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
        $this->withoutVite(); // las pruebas no dependen de que `npm run dev` esté corriendo
        Role::findOrCreate('alumno', 'web');
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('tutor', 'web');
        Role::findOrCreate('responsable', 'web');
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

    private ?User $responsableAsignado = null;

    /** Responsable que pasa lista; queda asignado a todas las actividades de la prueba. */
    private function responsable(): User
    {
        return $this->responsableAsignado ??= User::factory()->create()->assignRole('responsable');
    }

    private function actividad(array $attrs = []): Activity
    {
        return Activity::create(array_merge([
            'responsible_id' => $this->responsable()->id,
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
        $this->actingAs($alumno)->get(route('alumno.pases'))->assertOk()->assertSee('IA generativa');
    }

    public function test_cada_inscripcion_genera_un_pase_qr_unico(): void
    {
        $alumno = $this->alumno();
        $a = $this->actividad(['title' => 'Docker', 'start_time' => '09:00', 'end_time' => '10:00']);
        $b = $this->actividad(['title' => 'Laravel', 'start_time' => '11:00', 'end_time' => '12:00']);

        $this->actingAs($alumno)->post(route('alumno.inscripcion.store', $a))->assertSessionHas('success_link');
        $this->actingAs($alumno)->post(route('alumno.inscripcion.store', $b));

        $tokens = Enrollment::pluck('qr_token');
        $this->assertCount(2, $tokens->filter()->unique());

        $this->actingAs($alumno)->get(route('alumno.pases'))
            ->assertOk()
            ->assertSee($tokens[0])
            ->assertSee($tokens[1]);
    }

    public function test_un_alumno_no_entra_al_pase_de_lista(): void
    {
        $this->actingAs($this->alumno())->get(route('pase-lista.index'))->assertForbidden();
    }

    public function test_el_responsable_ve_la_lista_sin_los_codigos_reales(): void
    {
        $alumno = $this->alumno();
        $actividad = $this->actividad(['title' => 'Docker']);
        $e = Enrollment::create(['user_id' => $alumno->id, 'activity_id' => $actividad->id]);

        $this->actingAs($this->responsable())->get('/dashboard')->assertRedirect(route('pase-lista.index'));
        $this->actingAs($this->responsable())->get(route('pase-lista.index'))->assertOk()->assertSee('Docker');
        $this->actingAs($this->responsable())->get(route('pase-lista.show', $actividad))
            ->assertOk()
            ->assertSee($alumno->name)
            ->assertSee($e->qrHash())
            ->assertDontSee($e->qr_token);
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

    public function test_cancelar_y_reinscribirse_invalida_el_pase_anterior(): void
    {
        $alumno = $this->alumno();
        $actividad = $this->actividad();

        $this->actingAs($alumno)->post(route('alumno.inscripcion.store', $actividad));
        $viejo = Enrollment::first()->qr_token;
        $this->actingAs($alumno)->delete(route('alumno.inscripcion.destroy', $actividad));
        $this->actingAs($alumno)->post(route('alumno.inscripcion.store', $actividad));

        $this->assertNotSame($viejo, Enrollment::first()->qr_token);
    }

    public function test_el_responsable_escanea_el_pase_y_registra_la_asistencia(): void
    {
        $alumno = $this->alumno();
        $resp = $this->responsable();
        $actividad = $this->actividad(['checkin_enabled' => true]);
        $e = Enrollment::create(['user_id' => $alumno->id, 'activity_id' => $actividad->id]);

        $this->actingAs($resp)
            ->postJson(route('pase-lista.store', $actividad), ['token' => $e->qr_token])
            ->assertOk()
            ->assertJson(['ok' => true, 'already' => false, 'student' => ['name' => $alumno->name]]);

        $e->refresh();
        $this->assertTrue($e->attended);
        $this->assertSame($resp->id, $e->checked_in_by);

        // Escanear otra vez no es error
        $this->actingAs($resp)
            ->postJson(route('pase-lista.store', $actividad), ['token' => $e->qr_token])
            ->assertOk()
            ->assertJson(['already' => true]);
    }

    public function test_rechaza_el_pase_de_otra_actividad(): void
    {
        $alumno = $this->alumno();
        $docker = $this->actividad(['title' => 'Docker', 'checkin_enabled' => true]);
        $laravel = $this->actividad(['title' => 'Laravel', 'date' => '2026-11-19', 'checkin_enabled' => true]);
        $e = Enrollment::create(['user_id' => $alumno->id, 'activity_id' => $laravel->id]);

        $this->actingAs($this->responsable())
            ->postJson(route('pase-lista.store', $docker), ['token' => $e->qr_token])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Este pase es de «Laravel», no de esta actividad.']);
    }

    public function test_rechaza_si_el_pase_de_lista_esta_cerrado_y_el_responsable_puede_abrirlo(): void
    {
        $alumno = $this->alumno();
        $resp = $this->responsable();
        $actividad = $this->actividad(['checkin_enabled' => false]);
        $e = Enrollment::create(['user_id' => $alumno->id, 'activity_id' => $actividad->id]);

        $this->actingAs($resp)
            ->postJson(route('pase-lista.store', $actividad), ['token' => $e->qr_token])
            ->assertStatus(422);

        $this->actingAs($resp)->post(route('pase-lista.toggle', $actividad));
        $this->assertTrue($actividad->fresh()->checkin_enabled);

        $this->actingAs($resp)
            ->postJson(route('pase-lista.store', $actividad), ['token' => $e->qr_token])
            ->assertOk();
    }

    public function test_rechaza_pase_cancelado_o_inexistente(): void
    {
        $alumno = $this->alumno();
        $actividad = $this->actividad(['checkin_enabled' => true]);
        $e = Enrollment::create(['user_id' => $alumno->id, 'activity_id' => $actividad->id, 'status' => 'cancelled']);

        $this->actingAs($this->responsable())
            ->postJson(route('pase-lista.store', $actividad), ['token' => $e->qr_token])
            ->assertStatus(422);
        $this->actingAs($this->responsable())
            ->postJson(route('pase-lista.store', $actividad), ['token' => 'no-existe'])
            ->assertStatus(422);
    }

    public function test_registro_manual_del_responsable(): void
    {
        $alumno = $this->alumno();
        $actividad = $this->actividad(['checkin_enabled' => true]);
        $e = Enrollment::create(['user_id' => $alumno->id, 'activity_id' => $actividad->id]);

        $this->actingAs($this->responsable())
            ->post(route('pase-lista.manual', [$actividad, $e->id]))
            ->assertSessionHas('success');

        $this->assertTrue($e->fresh()->attended);
    }

    public function test_sincroniza_escaneos_guardados_sin_conexion(): void
    {
        Carbon::setTestNow('2026-11-18 15:00:00'); // Ya terminó; el responsable sincroniza después.

        $resp = $this->responsable();
        $actividad = $this->actividad(); // pase cerrado: sin conexión se valida por horario
        $a = Enrollment::create(['user_id' => $this->alumno()->id, 'activity_id' => $actividad->id]);
        $b = Enrollment::create(['user_id' => $this->alumno()->id, 'activity_id' => $actividad->id]);

        $res = $this->actingAs($resp)->postJson(route('pase-lista.sync', $actividad), [
            'items' => [
                // 11:05 hora de México (17:05 UTC), dentro del horario 11:00–13:00
                ['id' => 'a', 'token' => $a->qr_token, 'scanned_at' => '2026-11-18T17:05:00Z'],
                // 08:00 hora de México: fuera del horario
                ['id' => 'b', 'token' => $b->qr_token, 'scanned_at' => '2026-11-18T14:00:00Z'],
                ['id' => 'c', 'token' => 'no-existe', 'scanned_at' => '2026-11-18T17:05:00Z'],
            ],
        ])->assertOk();

        $resultados = collect($res->json('results'))->keyBy('id');
        $this->assertTrue($resultados['a']['ok']);
        $this->assertFalse($resultados['b']['ok']);
        $this->assertFalse($resultados['c']['ok']);

        $a->refresh();
        $this->assertTrue($a->attended);
        $this->assertTrue($a->synced_offline);
        $this->assertSame('11:05', $a->checkin_time->format('H:i'));
        $this->assertFalse($b->fresh()->attended);
    }

    public function test_solo_el_responsable_asignado_o_el_admin_pasan_lista(): void
    {
        $alumno = $this->alumno();
        $actividad = $this->actividad(['title' => 'Docker', 'checkin_enabled' => true]);
        $e = Enrollment::create(['user_id' => $alumno->id, 'activity_id' => $actividad->id]);

        // Otro responsable no ve ni puede pasar lista de esta actividad
        $otro = User::factory()->create()->assignRole('responsable');
        $this->actingAs($otro)->get(route('pase-lista.index'))->assertOk()->assertDontSee('Docker');
        $this->actingAs($otro)->get(route('pase-lista.show', $actividad))->assertForbidden();
        $this->actingAs($otro)->postJson(route('pase-lista.store', $actividad), ['token' => $e->qr_token])->assertForbidden();
        $this->actingAs($otro)->post(route('pase-lista.manual', [$actividad, $e->id]))->assertForbidden();

        // Un tutor ya no pasa lista
        $tutor = User::factory()->create()->assignRole('tutor');
        $this->actingAs($tutor)->get(route('pase-lista.index'))->assertForbidden();

        // El administrador sí puede
        $admin = User::factory()->create()->assignRole('admin');
        $this->actingAs($admin)->get(route('pase-lista.show', $actividad))->assertOk();
        $this->actingAs($admin)->postJson(route('pase-lista.store', $actividad), ['token' => $e->qr_token])->assertOk();
        $this->assertSame($admin->id, $e->fresh()->checked_in_by);
    }

    public function test_las_horas_de_la_agenda_usan_duration_hours(): void
    {
        $alumno = $this->alumno();
        $actividad = $this->actividad(['duration_hours' => 3]); // horario de 2 h, pero acredita 3
        Enrollment::create(['user_id' => $alumno->id, 'activity_id' => $actividad->id, 'attended' => true]);

        $this->actingAs($alumno)->get(route('alumno.agenda'))->assertOk()->assertSee('3 / 20 hrs');
    }
}
