<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Búsqueda mientras escribes (resources/js/filtro-en-vivo.js).
 *
 * El script pide la misma página con la búsqueda y reemplaza las partes
 * marcadas con data-resultados. Si una pantalla deja de traer alguna, el
 * script recarga la página completa; estas pruebas avisan antes.
 */
class FiltroEnVivoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        foreach (['admin', 'super_admin', 'alumno', 'tutor', 'docente', 'responsable'] as $rol) {
            Role::findOrCreate($rol, 'web');
        }
    }

    /** Pantalla => partes que se actualizan al escribir */
    public static function pantallasDelAdmin(): array
    {
        return [
            'talleres' => ['/admin/activities', ['limpiar', 'tabla', 'paginacion']],
            'ponentes' => ['/admin/speakers', ['limpiar', 'tabla', 'paginacion']],
            'responsables' => ['/admin/responsibles', ['limpiar', 'tabla', 'paginacion']],
            'administradores' => ['/admin/administrators', ['limpiar', 'tabla', 'paginacion']],
            'tutores' => ['/admin/tutors', ['limpiar', 'tabla', 'paginacion']],
            'certificados' => ['/admin/certificados', ['pestanas', 'tabla', 'paginacion']],
            'asistencias' => ['/admin/asistencias', ['limpiar', 'tabla', 'paginacion']],
            'asistencias por grupo' => ['/admin/asistencias/grupos', ['limpiar', 'tabla', 'paginacion']],
            'alumnos sin grupo' => ['/admin/asistencias/grupos/sin-grupo', ['pestanas', 'tabla', 'paginacion']],
        ];
    }

    private function assertFiltroVivo($respuesta, array $partes, string $busqueda): void
    {
        $respuesta->assertOk()
            ->assertSee('data-filtro-vivo', false)
            ->assertSee('value="'.$busqueda.'"', false); // el buscador conserva lo escrito al recargar

        foreach ($partes as $parte) {
            $this->assertSame(1, substr_count($respuesta->getContent(), 'data-resultados="'.$parte.'"'), "Falta (o se repite) la parte «{$parte}»");
        }
    }

    #[DataProvider('pantallasDelAdmin')]
    public function test_las_busquedas_del_admin_se_actualizan_al_escribir(string $ruta, array $partes): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->assertFiltroVivo($this->actingAs($admin)->get($ruta.'?q=Mar'), $partes, 'Mar');
        // Sin búsqueda también deben estar (el script las busca en la página actual)
        $this->assertFiltroVivo($this->actingAs($admin)->get($ruta), $partes, '');
    }

    public function test_la_asignacion_de_alumnos_de_un_tutor(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $tutor = User::factory()->create()->assignRole('tutor');
        User::factory()->create(['name' => 'Mariana Ruiz', 'matricula' => '2023000001'])->assignRole('alumno');

        $respuesta = $this->actingAs($admin)->get(route('admin.tutors.students', $tutor).'?q=Mar');

        $this->assertFiltroVivo($respuesta, ['limpiar', 'alumnos', 'paginacion'], 'Mar');
        $respuesta->assertSee('Mariana Ruiz')
            // Asignar «los N del filtro» usa la búsqueda vigente
            ->assertSee('<input type="hidden" name="q" value="Mar">', false);
    }

    public function test_el_detalle_de_asistencia(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $aula = \App\Models\Room::create(['name' => 'Aula 1', 'capacity' => 30]);
        $tipo = \App\Models\ActivityType::create(['name' => 'Taller']);
        $actividad = \App\Models\Activity::create(['activity_type_id' => $tipo->id, 'room_id' => $aula->id, 'title' => 'Taller de Redes',
            'date' => '2026-11-24', 'start_time' => '10:00', 'end_time' => '12:00', 'capacity' => 20]);

        $this->assertFiltroVivo(
            $this->actingAs($admin)->get(route('admin.attendances.show', $actividad).'?q=Mar'),
            ['pestanas', 'tabla', 'paginacion'],
            'Mar'
        );
    }

    public function test_el_catalogo_del_alumno(): void
    {
        $alumno = User::factory()->create()->assignRole('alumno');

        $this->assertFiltroVivo(
            $this->actingAs($alumno)->get(route('alumno.catalogo').'?q=Redes'),
            ['dias', 'tipos', 'actividades'],
            'Redes'
        );
    }

    public function test_los_tutorados_del_tutor(): void
    {
        $tutor = User::factory()->create()->assignRole('tutor');

        $this->assertFiltroVivo(
            $this->actingAs($tutor)->get(route('tutor.tutorados').'?q=Ana'),
            ['reporte', 'estados', 'tutorados'],
            'Ana'
        );
    }
}
