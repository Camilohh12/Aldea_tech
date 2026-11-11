<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tipos de actividad: alta, edición y baja desde el panel admin.
 */
class GestionTiposActividadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        foreach (['admin', 'super_admin', 'alumno', 'docente', 'responsable'] as $rol) {
            Role::findOrCreate($rol, 'web');
        }
        $this->admin = User::factory()->create()->assignRole('admin');
    }

    private function actividad(ActivityType $tipo): Activity
    {
        return Activity::create([
            'activity_type_id' => $tipo->id,
            'title' => 'Taller de prueba',
            'date' => '2026-11-24',
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);
    }

    public function test_solo_los_administradores_entran(): void
    {
        $alumno = User::factory()->create()->assignRole('alumno');

        $this->actingAs($alumno)->get(route('admin.activity-types.index'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('admin.activity-types.index'))->assertOk();
    }

    public function test_la_lista_muestra_los_tipos_y_el_menu_tiene_el_enlace(): void
    {
        $tipo = ActivityType::create(['name' => 'Taller', 'description' => 'Práctica estructurada']);
        $this->actividad($tipo);

        $this->actingAs($this->admin)->get(route('admin.activity-types.index'))
            ->assertOk()
            ->assertSee('Taller')
            ->assertSee('Práctica estructurada')
            ->assertSee('1 actividad')
            ->assertSee('href="'.route('admin.activity-types.index').'"', false);
    }

    public function test_un_tipo_nuevo_aparece_al_crear_una_actividad(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.activity-types.store'), ['name' => 'Hackatón', 'description' => 'Competencia de desarrollo'])
            ->assertRedirect(route('admin.activity-types.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('activity_types', ['name' => 'Hackatón', 'description' => 'Competencia de desarrollo']);

        $this->actingAs($this->admin)->get(route('admin.activities.create'))
            ->assertOk()
            ->assertSee('Hackatón');
    }

    public function test_la_descripcion_es_opcional_y_el_nombre_no(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.activity-types.store'), ['name' => 'Foro'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin)
            ->post(route('admin.activity-types.store'), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'El nombre del tipo es obligatorio.']);
    }

    public function test_no_se_repiten_los_nombres(): void
    {
        ActivityType::create(['name' => 'Taller']);

        $this->actingAs($this->admin)
            ->post(route('admin.activity-types.store'), ['name' => 'Taller'])
            ->assertSessionHasErrors(['name' => 'Ya existe un tipo de actividad con ese nombre.']);

        $this->assertSame(1, ActivityType::count());
    }

    public function test_editar_cambia_el_tipo_y_conserva_sus_actividades(): void
    {
        $tipo = ActivityType::create(['name' => 'Taller']);
        $actividad = $this->actividad($tipo);

        // Guardar sin cambiar el nombre no cuenta como repetido
        $this->actingAs($this->admin)
            ->put(route('admin.activity-types.update', $tipo), ['name' => 'Taller', 'description' => 'Con práctica'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin)
            ->put(route('admin.activity-types.update', $tipo), ['name' => 'Laboratorio'])
            ->assertRedirect(route('admin.activity-types.index'));

        $this->assertSame('Laboratorio', $tipo->fresh()->name);
        $this->assertSame($tipo->id, $actividad->fresh()->activity_type_id);
    }

    public function test_se_elimina_un_tipo_sin_actividades(): void
    {
        $tipo = ActivityType::create(['name' => 'Foro']);

        $this->actingAs($this->admin)
            ->delete(route('admin.activity-types.destroy', $tipo))
            ->assertRedirect(route('admin.activity-types.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('activity_types', ['id' => $tipo->id]);
    }

    public function test_no_se_elimina_un_tipo_con_actividades(): void
    {
        $tipo = ActivityType::create(['name' => 'Taller']);
        $actividad = $this->actividad($tipo);

        $this->actingAs($this->admin)
            ->delete(route('admin.activity-types.destroy', $tipo))
            ->assertRedirect(route('admin.activity-types.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('activity_types', ['id' => $tipo->id]);
        $this->assertDatabaseHas('activities', ['id' => $actividad->id]);
    }
}
