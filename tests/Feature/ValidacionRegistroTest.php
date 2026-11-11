<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Validaciones del registro de alumnos (matrícula, nombre, correo, contraseña)
 * y qué pasa si alguien intenta una inyección SQL en el formulario.
 */
class ValidacionRegistroTest extends TestCase
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

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'matricula' => '2023084921',
            'name' => 'Valeria Martínez Solís',
            'carrera' => 'Ingeniería en Software',
            'cuatrimestre' => '9',
            'grupo' => '1',
            'email' => 'valeria.martinez@micorreo.upp.edu.mx',
            'password' => 'Segura1',
            'password_confirmation' => 'Segura1',
            'acepto' => '1',
        ], $cambios);
    }

    // ------------------------------------------------------------ Formulario

    public function test_el_formulario_aplica_las_reglas_en_el_navegador(): void
    {
        $this->get('/register')->assertOk()
            ->assertSee('maxlength="50"', false)                                  // nombre
            ->assertSee('pattern="[0-9]{10}"', false)                             // matrícula
            ->assertSee("x-on:input=\"\$el.value = \$el.value.replace(/[^0-9]/g, '')\"", false)
            ->assertSee('pattern="[^@\s]+@micorreo\.upp\.edu\.mx"', false)       // correo
            ->assertSee('placeholder="nombre.apellido@micorreo.upp.edu.mx"', false)
            ->assertSee('Exclusivo @micorreo.upp.edu.mx')
            ->assertSee('minlength="6"', false)                                   // contraseña
            ->assertSee('pattern="(?=.*\p{Lu}).{6,}"', false)
            ->assertSee('Una letra mayúscula');
    }

    // ------------------------------------------------------------ Matrícula

    public function test_la_matricula_solo_acepta_numeros(): void
    {
        foreach (['20230849A1', '2023 08492', '2023-084921', '２０２３０８４９２１', '+2023084921', '2.02308492e9'] as $mala) {
            $this->post('/register', $this->datos(['matricula' => $mala]))
                ->assertSessionHasErrors(['matricula' => 'La matrícula debe tener 10 dígitos, sin espacios ni letras.']);
        }
        $this->assertSame(0, User::count());
    }

    // ------------------------------------------------------------ Nombre

    public function test_el_nombre_tiene_maximo_50_caracteres(): void
    {
        $this->post('/register', $this->datos(['name' => str_repeat('a', 51)]))
            ->assertSessionHasErrors(['name' => 'El nombre puede tener máximo 50 caracteres.']);

        // 50 justos (con acentos: cuenta letras, no bytes)
        $base = 'María José Ñúñez';
        $cincuenta = $base.str_repeat('a', 50 - mb_strlen($base));
        $this->assertSame(50, mb_strlen($cincuenta));
        $this->post('/register', $this->datos(['name' => $cincuenta]))->assertSessionHasNoErrors();
    }

    public function test_el_perfil_del_alumno_tambien_limita_el_nombre(): void
    {
        $alumno = User::factory()->create([
            'email' => 'ana@micorreo.upp.edu.mx', 'carrera' => 'Ingeniería en Software', 'cuatrimestre' => '7',
        ])->assignRole('alumno');

        $this->actingAs($alumno)->get(route('profile.edit'))->assertSee('maxlength="50"', false);
        $this->actingAs($alumno)->patch(route('profile.update'), [
            'name' => str_repeat('a', 51), 'email' => $alumno->email,
            'carrera' => 'Ingeniería en Software', 'cuatrimestre' => '7', 'grupo' => '1',
        ])->assertSessionHasErrors(['name' => 'El nombre puede tener máximo 50 caracteres.']);
    }

    // ------------------------------------------------------------ Correo

    public function test_el_alumno_que_cambia_su_correo_debe_usar_micorreo(): void
    {
        $alumno = User::factory()->create([
            'email' => 'ana@upp.edu.mx', 'carrera' => 'Ingeniería en Software', 'cuatrimestre' => '7', 'grupo' => 'sftw_01_07',
        ])->assignRole('alumno');
        $datos = ['name' => 'Ana Torres', 'carrera' => 'Ingeniería en Software', 'cuatrimestre' => '7', 'grupo' => '1'];

        // Su correo anterior se respeta mientras no lo cambie
        $this->actingAs($alumno)->patch(route('profile.update'), $datos + ['email' => 'ana@upp.edu.mx'])->assertSessionHasNoErrors();

        $this->actingAs($alumno)->patch(route('profile.update'), $datos + ['email' => 'ana.torres@upp.edu.mx'])
            ->assertSessionHasErrors(['email' => 'Usa tu correo institucional (@micorreo.upp.edu.mx).']);
        $this->actingAs($alumno)->patch(route('profile.update'), $datos + ['email' => 'ana.torres@micorreo.upp.edu.mx'])
            ->assertSessionHasNoErrors();
    }

    // ------------------------------------------------------------ Contraseña

    public function test_la_contrasena_pide_6_caracteres_y_una_mayuscula(): void
    {
        $this->post('/register', $this->datos(['password' => 'Abc12', 'password_confirmation' => 'Abc12']))
            ->assertSessionHasErrors(['password' => 'La contraseña debe tener al menos 6 caracteres.']);

        $this->post('/register', $this->datos(['password' => 'abc123', 'password_confirmation' => 'abc123']))
            ->assertSessionHasErrors(['password' => 'La contraseña debe incluir al menos una letra mayúscula.']);

        $this->post('/register', $this->datos(['password' => 'Abc123', 'password_confirmation' => 'Abc124']))
            ->assertSessionHasErrors(['password' => 'La confirmación de contraseña no coincide.']);
        $this->assertGuest();

        // 6 justos con una mayúscula (también acentuada)
        $this->post('/register', $this->datos(['password' => 'Ñandú1', 'password_confirmation' => 'Ñandú1']))
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('Ñandú1', User::first()->password));
    }

    public function test_cambiar_y_recuperar_la_contrasena_usan_la_misma_regla(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/profile')->put('/password', [
            'current_password' => 'password', 'password' => 'sinmayuscula', 'password_confirmation' => 'sinmayuscula',
        ])->assertSessionHasErrorsIn('updatePassword', ['password' => 'La contraseña debe incluir al menos una letra mayúscula.']);

        $this->actingAs($user)->from('/profile')->put('/password', [
            'current_password' => 'password', 'password' => 'Nueva1', 'password_confirmation' => 'Nueva1',
        ])->assertSessionHasNoErrors();

        auth()->logout();
        $token = \Illuminate\Support\Facades\Password::createToken($user);
        $this->post('/reset-password', [
            'token' => $token, 'email' => $user->email, 'password' => 'corta', 'password_confirmation' => 'corta',
        ])->assertSessionHasErrors(['password' => 'La contraseña debe tener al menos 6 caracteres.']);
    }

    // ------------------------------------------------------------ Inyección SQL

    /** Textos típicos de inyección SQL. */
    public static function inyecciones(): array
    {
        return [
            'comilla y OR' => ["' OR '1'='1"],
            'comentario' => ["admin'#"],
            'DROP TABLE' => ["x'); DROP TABLE users; --"],
            'UNION' => ["' UNION SELECT * FROM users --"],
            'punto y coma' => ['1; DELETE FROM users'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('inyecciones')]
    public function test_una_inyeccion_en_cualquier_campo_se_rechaza_sin_tocar_la_base(string $sql): void
    {
        $existente = User::factory()->create(['email' => 'ya.existe@micorreo.upp.edu.mx'])->assignRole('alumno');

        foreach (['matricula', 'name', 'email', 'carrera', 'cuatrimestre', 'grupo', 'tutor_id'] as $campo) {
            $this->post('/register', $this->datos([$campo => $sql]))
                ->assertSessionHasErrors($campo);
        }

        // La tabla sigue ahí, con el mismo usuario y sin cambios
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertSame(1, User::count());
        $this->assertSame('ya.existe@micorreo.upp.edu.mx', $existente->fresh()->email);
        $this->assertGuest();
    }

    public function test_si_un_texto_con_sql_es_valido_se_guarda_tal_cual(): void
    {
        // Un apóstrofo es válido en un nombre (O'Connor) y la contraseña admite
        // cualquier carácter: se guardan como texto, nunca se ejecutan.
        $contrasena = "A' OR '1'='1'; DROP TABLE users; --";

        $this->post('/register', $this->datos([
            'name' => "Brian O'Connor",
            'email' => "o'connor@micorreo.upp.edu.mx",
            'password' => $contrasena,
            'password_confirmation' => $contrasena,
        ]))->assertSessionHasNoErrors();

        $alumno = User::where('matricula', '2023084921')->first();
        $this->assertSame("Brian O'Connor", $alumno->name);
        $this->assertSame("o'connor@micorreo.upp.edu.mx", $alumno->email);
        $this->assertTrue(Hash::check($contrasena, $alumno->password)); // guardada cifrada, no en texto
        $this->assertTrue(Schema::hasTable('users'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('inyecciones')]
    public function test_una_inyeccion_en_el_inicio_de_sesion_no_entra(string $sql): void
    {
        User::factory()->create(['email' => 'ana@micorreo.upp.edu.mx', 'matricula' => '2023000001'])->assignRole('alumno');

        $this->post('/login', ['email' => $sql, 'password' => $sql]);
        $this->post('/login', ['email' => '2023000001', 'password' => $sql]);
        $this->post('/login', ['email' => "ana@micorreo.upp.edu.mx' --", 'password' => 'x']);

        $this->assertGuest();
        $this->assertSame(1, User::count());
    }
}
