<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Room;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Filtros de la URL, parámetros de las rutas y enlaces sin datos personales.
 */
class FiltrosYRutasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        foreach (['admin', 'super_admin', 'alumno', 'tutor', 'docente', 'responsable'] as $rol) {
            Role::findOrCreate($rol, 'web');
        }
        $this->admin = User::factory()->create()->assignRole('admin');
    }

    /** Pantallas con búsqueda o filtros y la URL manipulada que antes daba error 500. */
    public static function filtrosManipulados(): array
    {
        return [
            'talleres' => ['admin.activities.index', '?q[]=x&tipo[]=1&dia[]=2026-11-24'],
            'ponentes' => ['admin.speakers.index', '?q[]=x'],
            'responsables' => ['admin.responsibles.index', '?q[]=x'],
            'administradores' => ['admin.administrators.index', '?q[]=x'],
            'tutores' => ['admin.tutors.index', '?q[]=x'],
            'certificados' => ['admin.certificates.index', '?q[]=x&estado[]=listo'],
            'asistencias' => ['admin.attendances.index', '?q[]=x&dia=2026-02-31&tipo=abc&estado[]=1'],
            'reportes' => ['admin.reports.index', '?dia[]=x&tipo=1%20OR%201=1&carrera[]=x'],
        ];
    }

    #[DataProvider('filtrosManipulados')]
    public function test_los_filtros_manipulados_no_rompen_la_pantalla(string $ruta, string $query): void
    {
        $this->actingAs($this->admin)->get(route($ruta).$query)->assertOk();
    }

    public function test_la_portada_ignora_filtros_manipulados(): void
    {
        $this->get('/?tipo[]=1&dia[]=x')->assertOk();
        $this->get("/?tipo=1'%20OR%20'1'='1&dia=24-11-2026")->assertOk();
    }

    public function test_un_reporte_inexistente_responde_404(): void
    {
        $this->actingAs($this->admin)->get(route('admin.reports.index').'?reporte=usuarios')->assertNotFound();
        $this->actingAs($this->admin)->get(route('admin.reports.index').'?reporte[]=horas')->assertNotFound();
    }

    public function test_el_filtro_sigue_funcionando_con_valores_validos(): void
    {
        $taller = ActivityType::create(['name' => 'Taller']);
        $foro = ActivityType::create(['name' => 'Foro']);
        $aula = Room::create(['name' => 'Aula 1', 'capacity' => 30]);
        foreach ([[$taller, 'Taller de Redes'], [$foro, 'Foro de Software']] as [$tipo, $titulo]) {
            Activity::create(['activity_type_id' => $tipo->id, 'room_id' => $aula->id, 'title' => $titulo,
                'date' => '2026-11-24', 'start_time' => '10:00', 'end_time' => '12:00', 'capacity' => 20]);
        }

        $this->actingAs($this->admin)->get(route('admin.activities.index', ['tipo' => $taller->id, 'dia' => '2026-11-24']))
            ->assertOk()->assertSee('Taller de Redes')->assertDontSee('Foro de Software');

        // La búsqueda con comillas o comodines se trata como texto, nunca como SQL
        $this->actingAs($this->admin)->get(route('admin.activities.index', ['q' => "Redes' OR '1'='1"]))
            ->assertOk()->assertDontSee('Foro de Software');
    }

    public function test_los_ids_de_las_rutas_solo_aceptan_numeros(): void
    {
        $this->actingAs($this->admin)->get('/admin/rooms/abc/edit')->assertNotFound();
        $this->actingAs($this->admin)->get('/admin/asistencias/1%20OR%201=1')->assertNotFound();
        $this->actingAs($this->admin)->get('/admin/certificados/1/x/pdf')->assertNotFound();
    }

    public function test_una_url_distinta_no_sustituye_la_autorizacion(): void
    {
        $alumno = User::factory()->create()->assignRole('alumno');
        $tutor = User::factory()->create()->assignRole('tutor');
        $ajeno = User::factory()->create(['tutor_id' => null])->assignRole('alumno');

        // Un alumno no entra al panel aunque conozca la dirección
        $this->actingAs($alumno)->get('/admin/asistencias')->assertForbidden();
        // Un tutor no ve a un alumno que no es suyo aunque cambie el id en la URL
        $this->actingAs($tutor)->get(route('tutor.tutorados.show', $ajeno))->assertNotFound();
    }

    public function test_el_enlace_para_restablecer_la_contrasena_no_lleva_el_correo(): void
    {
        Notification::fake();
        $alumno = User::factory()->create(['email' => 'ana.torres@upp.edu.mx'])->assignRole('alumno');

        $this->post(route('password.email'), ['email' => 'ana.torres@upp.edu.mx']);

        Notification::assertSentTo($alumno, ResetPassword::class, function (ResetPassword $aviso) use ($alumno) {
            $url = $aviso->toMail($alumno)->actionUrl;

            $this->assertStringNotContainsString('email=', $url);
            $this->assertStringNotContainsString('upp.edu.mx', parse_url($url, PHP_URL_PATH).parse_url($url, PHP_URL_QUERY));

            // El formulario pide el correo y la contraseña se cambia con el token del enlace
            $this->get($url)->assertOk()->assertSee('name="email"', false);
            $this->post(route('password.store'), [
                'token' => $aviso->token,
                'email' => 'ana.torres@upp.edu.mx',
                'password' => 'Nueva-clave-123',
                'password_confirmation' => 'Nueva-clave-123',
            ])->assertSessionHasNoErrors();

            return true;
        });
    }
}
