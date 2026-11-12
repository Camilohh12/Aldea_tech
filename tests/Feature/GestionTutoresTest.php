<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tutores: alta desde el panel admin, asignación de alumnos,
 * elección del tutor en el registro y reporte PDF del tutor.
 */
class GestionTutoresTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Carbon::setTestNow('2026-11-25 12:00:00');
        foreach (['admin', 'super_admin', 'tutor', 'alumno', 'docente', 'responsable', 'ponente'] as $rol) {
            Role::findOrCreate($rol, 'web');
        }
        $this->admin = User::factory()->create()->assignRole('admin');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function tutor(string $name = 'Mtra. Laura Gómez', array $attrs = []): User
    {
        return User::factory()->create(['name' => $name] + $attrs)->assignRole('tutor');
    }

    private function alumno(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'carrera' => 'Ingeniería en Software',
            'cuatrimestre' => '7',
        ], $attrs))->assignRole('alumno');
    }

    // ------------------------------------------------------------ Panel admin

    public function test_solo_los_administradores_gestionan_tutores(): void
    {
        $this->actingAs($this->admin)->get(route('admin.tutors.index'))->assertOk()->assertSee('Gestión de Tutores');

        foreach (['alumno', 'tutor', 'responsable'] as $rol) {
            $usuario = User::factory()->create()->assignRole($rol);
            $this->actingAs($usuario)->get(route('admin.tutors.index'))->assertForbidden();
        }
    }

    public function test_el_admin_registra_un_tutor(): void
    {
        $this->actingAs($this->admin)->post(route('admin.tutors.store'), [
            'name' => 'Dr. Ramón Ortiz',
            'email' => 'ROrtiz@upp.edu.mx',
            'carrera' => 'Ingeniería en Software',
            'password' => 'temporal123',
            'password_confirmation' => 'temporal123',
        ])->assertSessionHasNoErrors();

        $tutor = User::where('email', 'rortiz@upp.edu.mx')->first();
        $this->assertTrue($tutor->hasRole('tutor'));
        $this->assertSame('Ingeniería en Software', $tutor->carrera);
        $this->assertNotNull($tutor->email_verified_at);

        // Puede entrar a su panel con la contraseña temporal
        auth()->logout();
        $this->post('/login', ['email' => 'rortiz@upp.edu.mx', 'password' => 'temporal123']);
        $this->get('/dashboard')->assertRedirect(route('tutor.tutorados'));
    }

    public function test_asigna_los_alumnos_marcados(): void
    {
        $tutor = $this->tutor();
        [$a, $b, $c] = [$this->alumno(), $this->alumno(), $this->alumno()];
        $responsable = User::factory()->create()->assignRole('responsable');

        $this->actingAs($this->admin)
            ->post(route('admin.tutors.students.assign', $tutor), ['alumnos' => [$a->id, $b->id, $responsable->id]])
            ->assertSessionHas('success', 'Se asignaron 2 alumnos a Mtra. Laura Gómez.');

        $this->assertSame($tutor->id, $a->fresh()->tutor_id);
        $this->assertSame($tutor->id, $b->fresh()->tutor_id);
        $this->assertNull($c->fresh()->tutor_id);
        $this->assertNull($responsable->fresh()->tutor_id, 'Solo se asignan cuentas de alumno');

        $this->actingAs($this->admin)
            ->post(route('admin.tutors.students.assign', $tutor), [])
            ->assertSessionHas('error');
    }

    public function test_asigna_en_bloque_por_carrera_y_cuatrimestre(): void
    {
        $tutor = $this->tutor();
        $otroTutor = $this->tutor('Ing. Pedro Ruiz');

        $software7 = $this->alumno();
        $software7b = $this->alumno();
        $software3 = $this->alumno(['cuatrimestre' => '3']);
        $redes7 = $this->alumno(['carrera' => 'Ingeniería en Redes y Telecomunicaciones']);
        $conOtroTutor = $this->alumno(['tutor_id' => $otroTutor->id]);

        $filtro = ['carrera' => 'Ingeniería en Software', 'cuatrimestre' => '7'];

        // La pantalla de asignación muestra solo los que coinciden y no tienen tutor
        $this->actingAs($this->admin)->get(route('admin.tutors.students', [$tutor, ...$filtro]))
            ->assertOk()
            ->assertSee($software7->name)
            ->assertDontSee($software3->name)
            ->assertDontSee($redes7->name)
            ->assertDontSee($conOtroTutor->name);

        $this->actingAs($this->admin)
            ->post(route('admin.tutors.students.assign', $tutor), $filtro + ['todos' => '1'])
            ->assertSessionHas('success', 'Se asignaron 2 alumnos a Mtra. Laura Gómez.');

        $this->assertSame($tutor->id, $software7->fresh()->tutor_id);
        $this->assertSame($tutor->id, $software7b->fresh()->tutor_id);
        $this->assertNull($software3->fresh()->tutor_id);
        $this->assertNull($redes7->fresh()->tutor_id);
        $this->assertSame($otroTutor->id, $conOtroTutor->fresh()->tutor_id, 'Sin "mostrar todos" no se mueve a alumnos de otro tutor');

        // Con "Todos los alumnos" sí se reasigna al que tenía otro tutor
        $this->actingAs($this->admin)
            ->post(route('admin.tutors.students.assign', $tutor), $filtro + ['mostrar' => 'todos', 'todos' => '1'])
            ->assertSessionHas('success');
        $this->assertSame($tutor->id, $conOtroTutor->fresh()->tutor_id);
    }

    public function test_quita_un_alumno_de_su_tutor(): void
    {
        $tutor = $this->tutor();
        $otroTutor = $this->tutor('Ing. Pedro Ruiz');
        $alumno = $this->alumno(['tutor_id' => $tutor->id]);
        $ajeno = $this->alumno(['tutor_id' => $otroTutor->id]);

        $this->actingAs($this->admin)->get(route('admin.tutors.edit', $tutor))->assertOk()->assertSee($alumno->name);

        $this->actingAs($this->admin)
            ->delete(route('admin.tutors.students.unassign', [$tutor, $alumno]))
            ->assertSessionHas('success');
        $this->assertNull($alumno->fresh()->tutor_id);

        // No se puede quitar a un alumno que es de otro tutor
        $this->actingAs($this->admin)
            ->delete(route('admin.tutors.students.unassign', [$tutor, $ajeno]))
            ->assertNotFound();
        $this->assertSame($otroTutor->id, $ajeno->fresh()->tutor_id);
    }

    public function test_al_eliminar_un_tutor_sus_alumnos_quedan_sin_tutor(): void
    {
        $tutor = $this->tutor();
        $alumno = $this->alumno(['tutor_id' => $tutor->id]);

        $this->actingAs($this->admin)->delete(route('admin.tutors.destroy', $tutor))
            ->assertSessionHas('success', 'Tutor eliminado correctamente. Su alumno quedó sin tutor.');

        $this->assertModelMissing($tutor);
        $this->assertNull($alumno->fresh()->tutor_id);

        // Si también es docente, solo pierde el rol de tutor
        $docente = $this->tutor('Mtro. Docente Tutor');
        $docente->assignRole('docente');
        $this->actingAs($this->admin)->delete(route('admin.tutors.destroy', $docente))->assertSessionHas('success');
        $this->assertModelExists($docente);
        $this->assertFalse($docente->fresh()->hasRole('tutor'));
        $this->assertTrue($docente->fresh()->hasRole('docente'));
    }

    public function test_no_gestiona_cuentas_que_no_son_tutores(): void
    {
        $responsable = User::factory()->create()->assignRole('responsable');

        $this->actingAs($this->admin)->get(route('admin.tutors.edit', $responsable))->assertNotFound();
        $this->actingAs($this->admin)->get(route('admin.tutors.students', $responsable))->assertNotFound();
        $this->actingAs($this->admin)->delete(route('admin.tutors.destroy', $responsable))->assertNotFound();
        $this->assertModelExists($responsable);
    }

    // ------------------------------------------------------------ Registro del alumno

    public function test_el_registro_pide_tutor_cuando_hay_tutores(): void
    {
        $tutor = $this->tutor('Mtra. Laura Gómez', ['carrera' => 'Ingeniería en Software']);

        $this->get('/register')->assertOk()->assertSee('Tutor académico')->assertSee('Mtra. Laura Gómez');

        $this->post('/register', $this->datosRegistro())->assertSessionHasErrors('tutor_id');
        $this->assertGuest();

        // Un id que no es de tutor no se acepta
        $this->post('/register', $this->datosRegistro(['tutor_id' => $this->admin->id]))->assertSessionHasErrors('tutor_id');
        $this->assertGuest();

        $this->post('/register', $this->datosRegistro(['tutor_id' => $tutor->id]))->assertSessionHasNoErrors();
        $alumno = User::where('matricula', '2023084921')->first();
        $this->assertSame($tutor->id, $alumno->tutor_id);

        // Y el tutor ya lo ve en su panel
        $this->actingAs($tutor)->get(route('tutor.tutorados'))->assertSee('Valeria Martínez Solís');
    }

    public function test_sin_tutores_registrados_el_campo_no_aparece(): void
    {
        $this->get('/register')->assertOk()->assertDontSee('Tutor académico');

        $this->post('/register', $this->datosRegistro())->assertSessionHasNoErrors();
        $this->assertNull(User::where('matricula', '2023084921')->value('tutor_id'));
    }

    private function datosRegistro(array $cambios = []): array
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

    // ------------------------------------------------------------ Reporte PDF del tutor

    public function test_el_tutor_descarga_su_reporte_en_pdf(): void
    {
        $tutor = $this->tutor();
        $alumno = $this->alumno(['tutor_id' => $tutor->id]);
        $actividad = Activity::create(['title' => 'Taller', 'date' => '2026-11-24', 'start_time' => '10:00', 'end_time' => '12:00']);
        Enrollment::create(['user_id' => $alumno->id, 'activity_id' => $actividad->id, 'attended' => true, 'checked_in_by' => $this->admin->id]);

        $this->actingAs($tutor)->get(route('tutor.tutorados'))->assertSee(route('tutor.tutorados.reporte'));

        $res = $this->actingAs($tutor)->get(route('tutor.tutorados.reporte', ['estado' => 'en_progreso']));
        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('content-type'));
        $this->assertStringContainsString('attachment; filename="Reporte-tutorados-mtra-laura-gomez-2026-11-25.pdf"', $res->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $res->getContent());

        // Un tutor sin alumnos también puede descargarlo (sale vacío)
        $this->actingAs($this->tutor('Ing. Sin Alumnos'))->get(route('tutor.tutorados.reporte'))->assertOk();
    }

    public function test_el_reporte_solo_es_para_tutores(): void
    {
        foreach (['alumno', 'responsable', 'admin'] as $rol) {
            $usuario = User::factory()->create()->assignRole($rol);
            $this->actingAs($usuario)->get(route('tutor.tutorados.reporte'))->assertForbidden();
        }
    }

    // ------------------------------------------------------------ Cuentas sin sección

    public function test_un_ponente_ve_su_pagina_en_espanol(): void
    {
        $ponente = User::factory()->create(['name' => 'Dra. Ana García'])->assignRole('ponente');

        $this->actingAs($ponente)->get('/dashboard')
            ->assertOk()
            ->assertSee('Hola, Ana')
            ->assertSee('Tu cuenta de ponente está activa.')
            ->assertDontSee("You're logged in!");

        $sinRol = User::factory()->create();
        $this->actingAs($sinRol)->get('/dashboard')->assertOk()->assertSee('todavía no tiene una sección asignada');
    }
}
