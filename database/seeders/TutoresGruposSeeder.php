<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Tutores por grupo (archivo GRUPOS_TIID_Y_SFTW.xlsx de la coordinación).
 *
 * Crea a cada tutor con su nombre y sus grupos. Todavía no tienen acceso:
 * el correo es provisional (@correo-pendiente.local) y la contraseña es
 * aleatoria. Cuando lleguen los correos, el administrador los captura en
 * Admin → Tutores → Editar y genera la contraseña.
 *
 * Se puede correr varias veces: si el tutor ya existe, solo actualiza sus grupos.
 * Uso: php artisan db:seed --class=TutoresGruposSeeder
 */
class TutoresGruposSeeder extends Seeder
{
    /** Grupo => tutor, tal como vienen en el Excel. */
    private const GRUPOS = [
        'TIID_01_01' => 'YESSICA RUIZ SALAZAR',
        'TIID_01_02' => 'MAYRA FABIOLA GONZALEZ PERALTA',
        'TIID_01_03' => 'DIANA XOCHITL RUANO VARGAS',
        'TIID_03_01' => 'JAZMIN  RODRIGUEZ FLORES',
        'TIID_04_01' => 'MAYRA FABIOLA GONZALEZ PERALTA',
        'TIID_04_02' => 'ERIKA VERENICE CERON CORNEJO',
        'TIID_06_01' => 'MARCO WALDO ANGELES TENA',
        'TIID_07_01' => 'ARTURO AUSTRIA CORNEJO',
        'TIID_07_02' => 'CARLOS ROBERTO DOMINGUEZ MAYORGA',
        'STFW_09_01' => 'ALICIA ORTIZ MONTES ',
    ];

    /** Carrera según el prefijo del grupo (para agruparlos en el registro). */
    private const CARRERAS = [
        'TIID' => 'Ingeniería en Tecnologías de la Información e Innovación Digital',
        'STFW' => 'Ingeniería en Software',
        'SFTW' => 'Ingeniería en Software',
    ];

    public function run(): void
    {
        Role::findOrCreate('tutor', 'web');

        // Un tutor puede tener varios grupos: se juntan por nombre
        $porTutor = collect(self::GRUPOS)
            ->map(fn (string $nombre) => self::nombre($nombre))
            ->groupBy(fn (string $nombre) => $nombre, preserveKeys: true)
            ->map(fn ($grupos) => $grupos->keys()->sort()->values());

        $creados = 0;

        foreach ($porTutor as $nombre => $grupos) {
            $tutor = User::role('tutor')->whereRaw('LOWER(name) = ?', [mb_strtolower($nombre)])->first();
            $datos = [
                'grupos_tutoria' => $grupos->implode(', '),
                'carrera' => self::CARRERAS[Str::before($grupos->first(), '_')] ?? null,
            ];

            if ($tutor) {
                $tutor->update($datos);

                continue;
            }

            $tutor = User::create($datos + [
                'name' => $nombre,
                'email' => self::correoProvisional($nombre),
                'password' => Str::password(32), // nadie la conoce: sin acceso hasta que el admin ponga una
            ]);
            $tutor->assignRole('tutor');
            $creados++;
        }

        $this->command?->info("Tutores por grupo listos: {$creados} nuevos, " . ($porTutor->count() - $creados) . ' actualizados.');
    }

    /** «JAZMIN  RODRIGUEZ FLORES» → «Jazmin Rodriguez Flores». */
    public static function nombre(string $nombre): string
    {
        return mb_convert_case(preg_replace('/\s+/', ' ', trim($nombre)), MB_CASE_TITLE, 'UTF-8');
    }

    /** nombre.apellido@correo-pendiente.local, sin repetir. */
    private static function correoProvisional(string $nombre): string
    {
        $partes = explode(' ', Str::slug($nombre, ' '));
        $base = $partes[0] . '.' . ($partes[count($partes) - 2] ?? end($partes));
        $correo = "{$base}@" . User::DOMINIO_CORREO_PENDIENTE;

        for ($i = 2; User::where('email', $correo)->exists(); $i++) {
            $correo = "{$base}{$i}@" . User::DOMINIO_CORREO_PENDIENTE;
        }

        return $correo;
    }
}
