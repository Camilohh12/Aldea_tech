<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\Enrollment;
use App\Models\Speaker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConstanciasPonentesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Carbon::setTestNow('2026-11-30 12:00:00');
        foreach (['admin', 'alumno', 'ponente', 'tutor'] as $rol) {
            Role::create(['name' => $rol]);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function usuario(string $rol): User
    {
        return User::factory()->create()->assignRole($rol);
    }

    private function actividad(array $attrs = []): Activity
    {
        $speaker = Speaker::create(['name' => 'Mtro. Carlos Méndez', 'bio' => 'Especialista Cloud Security']);

        return Activity::create(array_merge([
            'title' => 'Taller de Ciberseguridad',
            'speaker_id' => $speaker->id,
            'date' => '2026-11-24',
            'start_time' => '10:00',
            'end_time' => '12:30',
        ], $attrs));
    }

    public function test_el_admin_ve_las_constancias_por_generar(): void
    {
        $this->actividad(['title' => 'Taller de Ciberseguridad']);
        $this->actividad(['title' => 'Foro 2027', 'date' => '2026-12-05']);
        Activity::create(['title' => 'Actividad sin ponente', 'date' => '2026-11-20', 'start_time' => '09:00', 'end_time' => '10:00']);

        $this->actingAs($this->usuario('admin'))->get(route('admin.constancias'))
            ->assertOk()
            ->assertSee('Taller de Ciberseguridad')
            ->assertSee('Generar PDF')
            ->assertSee('Foro 2027')
            ->assertSee('Bloqueada')
            ->assertSee('Actividad sin ponente');
    }

    public function test_el_admin_entra_directo_a_constancias(): void
    {
        $this->actingAs($this->usuario('admin'))->get('/dashboard')->assertRedirect(route('admin.constancias'));
    }

    public function test_el_admin_genera_el_pdf_de_una_actividad_terminada(): void
    {
        $actividad = $this->actividad();
        Enrollment::create(['user_id' => $this->usuario('alumno')->id, 'activity_id' => $actividad->id, 'attended' => true]);

        $res = $this->actingAs($this->usuario('admin'))->get(route('admin.constancias.pdf', $actividad));

        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $res->getContent());
    }

    public function test_no_genera_si_la_actividad_no_ha_terminado_o_no_tiene_ponente(): void
    {
        $admin = $this->usuario('admin');
        $futura = $this->actividad(['date' => '2026-12-05']);
        $sinPonente = Activity::create(['title' => 'X', 'date' => '2026-11-20', 'start_time' => '09:00', 'end_time' => '10:00']);

        $this->actingAs($admin)->get(route('admin.constancias.pdf', $futura))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.constancias.pdf', $sinPonente))->assertNotFound();
    }

    public function test_ponentes_tutores_y_alumnos_no_pueden_generarlas(): void
    {
        $actividad = $this->actividad();

        foreach (['ponente', 'tutor', 'alumno'] as $rol) {
            $u = $this->usuario($rol);
            $this->actingAs($u)->get(route('admin.constancias'))->assertForbidden();
            $this->actingAs($u)->get(route('admin.constancias.pdf', $actividad))->assertForbidden();
        }
    }

    public function test_el_folio_es_estable_y_con_formato(): void
    {
        $a = $this->actividad();

        $this->assertSame($a->speakerCertificateFolio(), $a->fresh()->speakerCertificateFolio());
        $this->assertMatchesRegularExpression('/^AT26-P-\d{5}-[0-9A-F]{6}$/', $a->speakerCertificateFolio());
    }
}
