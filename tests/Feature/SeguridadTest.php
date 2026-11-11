<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Enrollment;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ModuloAlumnoSeeder;
use Database\Seeders\ProduccionSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustHosts;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Arreglos de la revisión de seguridad antes de publicar. */
class SeguridadTest extends TestCase
{
    use RefreshDatabase;

    private const DEMASIADOS = 'Demasiados intentos de registro. Espera un minuto y vuelve a intentarlo.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        foreach (['admin', 'super_admin', 'alumno', 'tutor', 'docente', 'responsable'] as $rol) {
            Role::findOrCreate($rol, 'web');
        }
    }

    // ------------------------------------------------------------ Dominio de los enlaces

    public function test_el_enlace_para_recuperar_la_contrasena_usa_app_url(): void
    {
        config(['app.url' => 'https://aldea.upp.edu.mx']);
        Notification::fake();
        $user = User::factory()->create(['email' => 'ana@micorreo.upp.edu.mx']);

        // Quien pide el correo dice venir de otro dominio
        $this->post('http://sitio-falso.com/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $aviso) use ($user) {
            $enlace = $aviso->toMail($user)->actionUrl;

            return str_starts_with($enlace, 'https://aldea.upp.edu.mx/reset-password/')
                && ! str_contains($enlace, 'sitio-falso');
        });
    }

    public function test_solo_se_atiende_el_dominio_de_app_url(): void
    {
        // En local y en las pruebas no se aplica; aquí se revisa que esté puesto y qué dominios acepta
        $this->assertTrue($this->app->make(Kernel::class)->hasMiddleware(TrustHosts::class));

        config(['app.url' => 'https://aldea.upp.edu.mx']);
        $patrones = array_filter((new TrustHosts($this->app))->hosts());
        $acepta = fn (string $dominio) => collect($patrones)->contains(fn ($p) => preg_match('{'.$p.'}i', $dominio) === 1);

        $this->assertTrue($acepta('aldea.upp.edu.mx'));
        $this->assertTrue($acepta('healthcheck.railway.app'));   // revisión de /up en Railway
        $this->assertFalse($acepta('sitio-falso.com'));
        $this->assertFalse($acepta('aldea.upp.edu.mx.sitio-falso.com'));
    }

    // ------------------------------------------------------------ Seeders

    public function test_los_seeders_de_prueba_no_corren_en_produccion(): void
    {
        $this->app['env'] = 'production';

        foreach ([DatabaseSeeder::class, ModuloAlumnoSeeder::class] as $seeder) {
            try {
                $this->app->make($seeder)->run();
                $this->fail("{$seeder} no debe correr en producción.");
            } catch (\RuntimeException $e) {
                $this->assertStringContainsString('ProduccionSeeder', $e->getMessage());
            }
        }
        $this->assertSame(0, User::count()); // no dejó ninguna cuenta de prueba

        // El de producción sí corre: roles y tipos, y el Super Admin solo si se capturan sus datos
        $this->app->make(ProduccionSeeder::class)->run();
        $this->assertSame(4, ActivityType::count());
        $this->assertSame(0, User::count());
    }

    // ------------------------------------------------------------ Límite del registro

    public function test_el_registro_limita_los_intentos_con_la_misma_matricula(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/register', ['matricula' => '2023084921'])
                ->assertSessionHasErrors('name')
                ->assertSessionDoesntHaveErrors('matricula');
        }

        $this->post('/register', ['matricula' => '2023084921'])
            ->assertSessionHasErrors(['matricula' => self::DEMASIADOS]);

        // Otro alumno desde la misma red sí puede registrarse
        $this->post('/register', [
            'matricula' => '2023084922',
            'name' => 'Valeria Martínez Solís',
            'carrera' => 'Ingeniería en Software',
            'cuatrimestre' => '9',
            'grupo' => '1',
            'email' => 'valeria.martinez@micorreo.upp.edu.mx',
            'password' => 'Segura1',
            'password_confirmation' => 'Segura1',
            'acepto' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertAuthenticated();
    }

    public function test_el_limite_por_red_deja_registrarse_a_un_salon_completo(): void
    {
        // 60 alumnos distintos desde la misma IP (la red de la escuela)
        for ($i = 1; $i <= 60; $i++) {
            $this->post('/register', ['matricula' => (string) (2023000000 + $i)])
                ->assertSessionDoesntHaveErrors('matricula');
        }

        $this->post('/register', ['matricula' => '2023000099'])
            ->assertSessionHasErrors(['matricula' => self::DEMASIADOS]);
    }

    // ------------------------------------------------------------ CSV

    public function test_los_csv_no_dejan_pasar_formulas_de_excel(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $actividad = Activity::create([
            'activity_type_id' => ActivityType::firstOrCreate(['name' => 'Taller'])->id,
            'room_id' => Room::firstOrCreate(['name' => 'Aula 1'], ['capacity' => 30])->id,
            'title' => '=HIPERVINCULO("http://sitio-falso.com","Clic")',
            'date' => '2026-11-24', 'start_time' => '10:00', 'end_time' => '12:00',
            'capacity' => 30, 'duration_hours' => 2,
        ]);
        $alumno = User::factory()->create([
            'name' => '@SUMA(1+1)', 'email' => 'ana@micorreo.upp.edu.mx',
            'matricula' => '2023000001', 'carrera' => '+Software',
        ])->assignRole('alumno');
        Enrollment::create(['user_id' => $alumno->id, 'activity_id' => $actividad->id, 'attended' => true]);

        $asistencia = $this->actingAs($admin)->get(route('admin.attendances.export', $actividad))
            ->assertOk()->streamedContent();
        $this->assertStringContainsString("'@SUMA(1+1)", $asistencia);
        $this->assertStringContainsString("'+Software", $asistencia);

        $reporte = $this->actingAs($admin)->get(route('admin.reports.export', 'inscripciones'))
            ->assertOk()->streamedContent();
        $this->assertStringContainsString("'=HIPERVINCULO", $reporte);

        // Ninguna celda empieza con un signo que Excel ejecute
        foreach ([$asistencia, $reporte] as $csv) {
            $this->assertDoesNotMatchRegularExpression('/(^|,)"?[=+@-]/m', $csv);
        }
    }

    // ------------------------------------------------------------ Aviso de Privacidad

    public function test_el_registro_enlaza_el_reglamento_y_el_aviso_de_privacidad(): void
    {
        // Sin liga configurada se muestra solo el nombre
        $this->get('/register')->assertOk()
            ->assertSee('<strong class="text-aldea-800">Aviso de Privacidad</strong>', false)
            ->assertSee('<strong class="text-aldea-800">Reglamento de Participación</strong>', false);

        config([
            'aldea.enlaces.aviso_privacidad' => 'https://example.com/aviso-de-privacidad',
            'aldea.enlaces.reglamento' => 'https://example.com/reglamento',
        ]);

        $this->get('/register')->assertOk()
            ->assertSee('href="https://example.com/aviso-de-privacidad"', false)
            ->assertSee('href="https://example.com/reglamento"', false)
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertDontSee('<strong class="text-aldea-800">Aviso de Privacidad</strong>', false);
    }
}
