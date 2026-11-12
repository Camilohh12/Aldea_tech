<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\CertificateTemplate;
use App\Models\Room;
use App\Models\Speaker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Reconocimiento de ponentes con el formato oficial UPP 2026.
 */
class ReconocimientoOficialTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        foreach (['admin', 'super_admin', 'alumno', 'responsable', 'tutor'] as $rol) {
            Role::findOrCreate($rol, 'web');
        }
        $this->admin = User::factory()->create()->assignRole('admin');
    }

    private function actividadConPonente(): array
    {
        $actividad = Activity::create([
            'activity_type_id' => ActivityType::firstOrCreate(['name' => 'Taller'])->id,
            'room_id' => Room::firstOrCreate(['name' => 'Laboratorio 3'], ['capacity' => 30])->id,
            'title' => 'Ciberseguridad Defensiva',
            'date' => '2026-11-24', 'start_time' => '10:00', 'end_time' => '12:00', 'capacity' => 20,
        ]);
        $ponente = Speaker::create(['name' => 'Dra. Mónica Soto']);
        $actividad->speakers()->attach($ponente);

        return [$actividad->fresh(), $ponente];
    }

    public function test_la_plantilla_nueva_usa_los_textos_oficiales(): void
    {
        $plantilla = CertificateTemplate::current();

        $this->assertSame('RECONOCIMIENTO', $plantilla->title);
        $this->assertSame('La Universidad Politécnica de Pachuca otorga el presente', $plantilla->heading);
        $this->assertSame('Mtro. Francisco Hernández Jiménez', $plantilla->signer1_name);
        $this->assertSame('Rector', $plantilla->signer1_title);
    }

    public function test_el_texto_lleva_en_negritas_solo_la_actividad(): void
    {
        [$actividad, $ponente] = $this->actividadConPonente();
        $plantilla = CertificateTemplate::current();

        $html = $plantilla->renderBody($plantilla->valuesFor($actividad, $ponente));

        $this->assertStringContainsString('“<strong>Ciberseguridad Defensiva</strong>”, taller de la Aldea Tecnológica 2026', $html);
        $this->assertStringContainsString('el día 24 de noviembre de 2026, Zempoala, Hidalgo.', $html);
        $this->assertSame(1, substr_count($html, '<strong>'));
    }

    public function test_la_migracion_cambia_solo_los_textos_que_nadie_edito(): void
    {
        DB::table('certificate_templates')->delete();
        DB::table('certificate_templates')->insert([
            'institution' => 'UNIVERSIDAD POLITÉCNICA DE PACHUCA',
            'event_name' => 'Aldea Tecnológica 2026',
            'heading' => 'OTORGA LA PRESENTE',
            'title' => 'CONSTANCIA',
            'body' => 'Texto que escribió el administrador',
            'signer1_name' => 'Coordinación de Aldea Tecnológica',
            'signer1_title' => 'Universidad Politécnica de Pachuca',
            'footer' => 'Pachuca de Soto, Hidalgo',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        (require database_path('migrations/2026_11_12_000001_usar_formato_oficial_en_reconocimientos.php'))->up();

        $plantilla = CertificateTemplate::first();
        $this->assertSame('RECONOCIMIENTO', $plantilla->title);
        $this->assertSame('Rector', $plantilla->signer1_title);
        $this->assertNull($plantilla->footer);
        $this->assertSame('Texto que escribió el administrador', $plantilla->body);
    }

    public function test_el_pdf_usa_el_fondo_oficial_y_la_letra_inter(): void
    {
        $this->travelTo('2026-11-25 09:00');
        [$actividad, $ponente] = $this->actividadConPonente();

        $vista = null;
        View::composer('pdf.certificate', function ($v) use (&$vista) {
            $vista = $v;
        });

        $this->actingAs($this->admin)
            ->get(route('admin.certificates.download', [$actividad, $ponente]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $html = $vista->render();
        $this->assertStringContainsString('data:image/jpeg;base64,', $html);
        $this->assertStringContainsString('Inter-SemiBold.ttf', $html);
        $this->assertStringContainsString('RECONOCIMIENTO <span>a:</span>', $html);
        $this->assertStringContainsString('Dra. Mónica Soto', $html);
    }

    public function test_el_editor_muestra_la_vista_previa_con_el_fondo(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.certificates.template'))
            ->assertOk()
            ->assertSee('img/certificado/fondo-reconocimiento.jpg', false)
            ->assertSee('{dia}');
    }
}
