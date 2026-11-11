<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Room;
use App\Models\Speaker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Programa del evento en PDF desde el panel de administración.
 */
class ProgramaPdfTest extends TestCase
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

    private function actividad(string $titulo, string $fecha, string $inicio, string $fin, array $extra = []): Activity
    {
        $tipo = ActivityType::firstOrCreate(['name' => 'Taller']);
        $aula = Room::firstOrCreate(['name' => 'Laboratorio 3'], ['capacity' => 30]);

        return Activity::create([
            'activity_type_id' => $tipo->id, 'room_id' => $aula->id, 'title' => $titulo,
            'date' => $fecha, 'start_time' => $inicio, 'end_time' => $fin, 'capacity' => 20,
            ...$extra,
        ]);
    }

    /** Ejecuta la vista del PDF y devuelve el HTML que se manda a DomPDF. */
    private function htmlDelPrograma(?string $dia = null): string
    {
        $vista = null;
        \Illuminate\Support\Facades\View::composer('pdf.programa', function ($v) use (&$vista) {
            $vista = $v;
        });

        $this->actingAs($this->admin)->get(route('admin.program.pdf', $dia))->assertOk();

        return $vista->render();
    }

    public function test_descarga_el_programa_completo_en_pdf(): void
    {
        $this->actividad('Taller de Redes', '2026-11-24', '10:00', '12:00');

        $respuesta = $this->actingAs($this->admin)->get(route('admin.program.pdf'));

        $respuesta->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('Programa-Aldea-Tecnologica-2026.pdf');
        $this->assertStringStartsWith('%PDF', $respuesta->getContent());
    }

    public function test_descarga_un_archivo_por_cada_dia_del_evento(): void
    {
        $nombres = [
            '2026-11-24' => 'Programa-Aldea-Tecnologica-2026-martes-24-noviembre.pdf',
            '2026-11-25' => 'Programa-Aldea-Tecnologica-2026-miercoles-25-noviembre.pdf',
            '2026-11-26' => 'Programa-Aldea-Tecnologica-2026-jueves-26-noviembre.pdf',
        ];

        foreach ($nombres as $dia => $archivo) {
            $this->actingAs($this->admin)->get(route('admin.program.pdf', $dia))
                ->assertOk()->assertDownload($archivo);
        }
    }

    public function test_el_programa_va_en_orden_por_fecha_y_hora_con_sus_datos(): void
    {
        $responsable = User::factory()->create(['name' => 'Ing. Marco Ortiz'])->assignRole('responsable');
        $tarde = $this->actividad('Foro de Software', '2026-11-25', '16:00', '18:00', ['responsible_id' => $responsable->id]);
        $this->actividad('Conferencia inaugural', '2026-11-24', '09:00', '10:00');
        $this->actividad('Taller de Redes', '2026-11-25', '10:00', '12:00');
        $tarde->speakers()->attach(Speaker::create(['name' => 'Dra. Ana García']));

        $html = $this->htmlDelPrograma();

        $this->assertMatchesRegularExpression('/Conferencia inaugural.*Taller de Redes.*Foro de Software/s', $html);
        $this->assertMatchesRegularExpression('/Martes 24 de noviembre.*Miércoles 25 de noviembre.*Jueves 26 de noviembre/s', $html);
        foreach (['16:00 – 18:00', 'Laboratorio 3', 'Dra. Ana García', 'Ing. Marco Ortiz', 'Taller', 'Sin actividades programadas para este día.'] as $texto) {
            $this->assertStringContainsString($texto, $html);
        }
    }

    public function test_el_programa_de_un_dia_solo_trae_ese_dia(): void
    {
        $this->actividad('Conferencia inaugural', '2026-11-24', '09:00', '10:00');
        $this->actividad('Taller de Redes', '2026-11-25', '10:00', '12:00');

        $html = $this->htmlDelPrograma('2026-11-25');

        $this->assertStringContainsString('Taller de Redes', $html);
        $this->assertStringNotContainsString('Conferencia inaugural', $html);
        $this->assertStringContainsString('Programa del miércoles 25 de noviembre de 2026', $html);
    }

    public function test_lleva_la_identidad_del_evento_y_el_color_institucional(): void
    {
        $html = $this->htmlDelPrograma();

        $this->assertStringContainsString('Aldea Tecnológica 2026', $html);
        $this->assertStringContainsString('Universidad Politécnica de Pachuca', $html);
        $this->assertStringContainsString(config('aldea.marca.800'), $html);
        $this->assertSame('#3c1860', config('aldea.marca.800'));
    }

    public function test_un_dia_fuera_del_evento_o_mal_escrito_no_existe(): void
    {
        $this->actingAs($this->admin)->get('/admin/programa/pdf/2026-11-27')->assertNotFound();
        $this->actingAs($this->admin)->get('/admin/programa/pdf/24-11-2026')->assertNotFound();
        $this->actingAs($this->admin)->get('/admin/programa/pdf/2026-11-24%27%20OR%201=1')->assertNotFound();
    }

    public function test_solo_el_administrador_descarga_el_programa(): void
    {
        $alumno = User::factory()->create()->assignRole('alumno');

        $this->get(route('admin.program.pdf'))->assertRedirect(route('login'));
        $this->actingAs($alumno)->get(route('admin.program.pdf'))->assertForbidden();
    }

    public function test_la_programacion_muestra_los_enlaces_de_descarga(): void
    {
        $this->actingAs($this->admin)->get(route('admin.activities.index'))
            ->assertOk()
            ->assertSee('Descargar programa')
            ->assertSee(route('admin.program.pdf'), false)
            ->assertSee(route('admin.program.pdf', '2026-11-24'), false)
            ->assertSee(route('admin.program.pdf', '2026-11-26'), false);
    }
}
