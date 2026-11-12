<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Cronograma público: actividades por día, con las de hoy primero.
 */
class CronogramaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        foreach (['admin', 'alumno', 'tutor'] as $rol) {
            Role::findOrCreate($rol, 'web');
        }

        $this->actividad('Taller de Redes', '2026-11-24', '10:00', '12:00');
        $this->actividad('Conferencia Inaugural', '2026-11-24', '08:00', '09:00');
        $this->actividad('Curso de Laravel', '2026-11-25', '09:00', '11:00');
        $this->actividad('Foro de Egresados', '2026-11-25', '16:00', '18:00');
        $this->actividad('Clausura', '2026-11-26', '13:00', '14:00');
    }

    private function actividad(string $titulo, string $fecha, string $inicio, string $fin): Activity
    {
        return Activity::create([
            'activity_type_id' => ActivityType::firstOrCreate(['name' => 'Taller'])->id,
            'room_id' => Room::firstOrCreate(['name' => 'Laboratorio 3'], ['capacity' => 30])->id,
            'title' => $titulo, 'date' => $fecha, 'start_time' => $inicio, 'end_time' => $fin, 'capacity' => 20,
        ]);
    }

    public function test_antes_del_evento_muestra_los_tres_dias_en_orden(): void
    {
        $this->travelTo('2026-11-12 10:00');

        $this->get(route('cronograma'))->assertOk()
            ->assertSeeInOrder([
                'Martes 24 de noviembre', 'Conferencia Inaugural', 'Taller de Redes',
                'Miércoles 25 de noviembre', 'Curso de Laravel', 'Foro de Egresados',
                'Jueves 26 de noviembre', 'Clausura',
            ])
            ->assertDontSee('Actividades de hoy');
    }

    public function test_durante_el_evento_las_de_hoy_van_primero(): void
    {
        $this->travelTo('2026-11-25 10:00');

        $this->get(route('cronograma'))->assertOk()
            ->assertSeeInOrder([
                'Hoy · Miércoles 25 de noviembre', 'Actividades de hoy', 'En curso', 'Curso de Laravel', 'Foro de Egresados',
                'Martes 24 de noviembre', 'Finalizada', 'Conferencia Inaugural',
                'Jueves 26 de noviembre', 'Clausura',
            ]);
    }

    public function test_filtra_por_dia(): void
    {
        $this->travelTo('2026-11-12 10:00');

        $this->get(route('cronograma', ['dia' => '2026-11-26']))->assertOk()
            ->assertSee('Clausura')
            ->assertDontSee('Curso de Laravel')
            ->assertDontSee('Taller de Redes');

        // Un día que no existe o un valor raro muestra todo
        $this->get('/cronograma?dia=2030-01-01')->assertOk()->assertSee('Taller de Redes')->assertSee('Clausura');
        $this->get('/cronograma?dia[]=x')->assertOk()->assertSee('Clausura');
    }

    public function test_un_dia_sin_actividades_lo_dice(): void
    {
        Activity::whereDate('date', '2026-11-26')->delete();

        $this->get(route('cronograma', ['dia' => '2026-11-26']))->assertOk()
            ->assertSee('Aún no hay actividades programadas para este día.');
    }

    public function test_el_alumno_va_al_catalogo_de_ese_dia_y_el_visitante_al_login(): void
    {
        $this->travelTo('2026-11-12 10:00');

        $this->get(route('cronograma'))->assertSee('Inicia sesión para inscribirte');

        $alumno = User::factory()->create()->assignRole('alumno');
        $this->actingAs($alumno)->get(route('cronograma'))
            ->assertSee('Inscribirme')
            ->assertSee(route('alumno.catalogo', ['fecha' => '2026-11-24']), false);
    }

    public function test_la_portada_y_el_catalogo_enlazan_al_cronograma(): void
    {
        $this->get(route('home'))->assertOk()->assertSee(route('cronograma'), false);

        $alumno = User::factory()->create()->assignRole('alumno');
        $this->actingAs($alumno)->get(route('alumno.catalogo'))->assertOk()->assertSee('Ver el cronograma por día');
    }
}
