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
            Role::findOrCreate($rol, 'web');
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

    private function actividad(array $attrs = [], array $ponentes = ['Mtro. Carlos Méndez']): Activity
    {
        $actividad = Activity::create(array_merge([
            'title' => 'Taller de Ciberseguridad',
            'date' => '2026-11-24',
            'start_time' => '10:00',
            'end_time' => '12:30',
        ], $attrs));

        foreach ($ponentes as $nombre) {
            $actividad->speakers()->attach(Speaker::firstOrCreate(['name' => $nombre], ['bio' => 'Especialista']));
        }

        return $actividad->load('speakers');
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
            ->assertSee('Por realizarse')
            ->assertSee('Actividad sin ponente');
    }

    public function test_el_admin_entra_a_su_panel(): void
    {
        $this->actingAs($this->usuario('admin'))->get('/dashboard')->assertRedirect(route('admin.home'));
    }

    public function test_hay_una_constancia_por_cada_ponente_de_la_actividad(): void
    {
        $actividad = $this->actividad([], ['Mtro. Carlos Méndez', 'Dra. Ana García']);
        $admin = $this->usuario('admin');

        $res = $this->actingAs($admin)->get(route('admin.constancias'))->assertOk();
        foreach ($actividad->speakers as $speaker) {
            $res->assertSee($actividad->speakerCertificateFolio($speaker));
            $this->actingAs($admin)->get(route('admin.constancias.pdf', [$actividad, $speaker]))->assertOk();
        }

        [$a, $b] = $actividad->speakers;
        $this->assertNotSame($actividad->speakerCertificateFolio($a), $actividad->speakerCertificateFolio($b));
    }

    public function test_no_genera_constancia_de_un_ponente_que_no_participa(): void
    {
        $actividad = $this->actividad();
        $ajeno = Speaker::create(['name' => 'Ing. Ajeno']);

        $this->actingAs($this->usuario('admin'))
            ->get(route('admin.constancias.pdf', [$actividad, $ajeno]))
            ->assertNotFound();
    }

    public function test_el_admin_genera_el_pdf_de_una_actividad_terminada(): void
    {
        $actividad = $this->actividad();
        Enrollment::create(['user_id' => $this->usuario('alumno')->id, 'activity_id' => $actividad->id, 'attended' => true]);

        $res = $this->actingAs($this->usuario('admin'))->get(route('admin.constancias.pdf', [$actividad, $actividad->speakers->first()]));

        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $res->getContent());
    }

    public function test_no_genera_si_la_actividad_no_ha_terminado(): void
    {
        $futura = $this->actividad(['date' => '2026-12-05']);

        $this->actingAs($this->usuario('admin'))
            ->get(route('admin.constancias.pdf', [$futura, $futura->speakers->first()]))
            ->assertForbidden();
    }

    public function test_ponentes_tutores_y_alumnos_no_pueden_generarlas(): void
    {
        $actividad = $this->actividad();

        foreach (['ponente', 'tutor', 'alumno'] as $rol) {
            $u = $this->usuario($rol);
            $this->actingAs($u)->get(route('admin.constancias'))->assertForbidden();
            $this->actingAs($u)->get(route('admin.constancias.pdf', [$actividad, $actividad->speakers->first()]))->assertForbidden();
        }
    }

    public function test_el_folio_es_estable_y_con_formato(): void
    {
        $a = $this->actividad();
        $speaker = $a->speakers->first();

        $this->assertSame($a->speakerCertificateFolio($speaker), $a->fresh()->speakerCertificateFolio($speaker));
        $this->assertMatchesRegularExpression('/^AT26-P-\d{5}-\d{2}-[0-9A-F]{6}$/', $a->speakerCertificateFolio($speaker));
    }
}
