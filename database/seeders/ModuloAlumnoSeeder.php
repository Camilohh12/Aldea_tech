<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Enrollment;
use App\Models\Room;
use App\Models\Speaker;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Datos de prueba para el módulo Alumno.
 * Uso: php artisan db:seed --class=ModuloAlumnoSeeder
 */
class ModuloAlumnoSeeder extends Seeder
{
    public function run(): void
    {
        $rolAlumno = Role::firstOrCreate(['name' => 'alumno']);

        $alumno = User::firstOrCreate(
            ['email' => 'alumno@aldea.edu.mx'],
            [
                'name' => 'Valeria Martínez Solís',
                'password' => Hash::make('password123'),
                'matricula' => '202308492',
                'carrera' => 'Ingeniería en Software',
                'cuatrimestre' => '7',
                'email_verified_at' => now(),
            ],
        );
        $alumno->assignRole($rolAlumno);

        $tipos = ActivityType::pluck('id', 'name');

        $aulas = collect([
            ['name' => 'Auditorio Principal', 'capacity' => 250],
            ['name' => 'Laboratorio 3 (Edificio C)', 'capacity' => 30],
            ['name' => 'Aula Magna Tech (Piso 2)', 'capacity' => 40],
            ['name' => 'Sala de Usos Múltiples', 'capacity' => 60],
        ])->mapWithKeys(fn ($a) => [$a['name'] => Room::updateOrCreate(['name' => $a['name']], $a)]);

        $ponentes = collect([
            'Mtro. Carlos Méndez' => 'Especialista Cloud Security',
            'Ing. Sofía Morales' => 'Lead Backend Architect',
            'Dra. Ana García' => 'Directora de Investigación ML',
            'Ing. Roberto Valenzuela' => 'Analista forense digital',
        ])->mapWithKeys(fn ($bio, $nombre) => [$nombre => Speaker::updateOrCreate(['name' => $nombre], ['bio' => $bio])]);

        // Responsable que pasa lista (rol creado en la migración create_responsable_role)
        $responsable = User::firstOrCreate(
            ['email' => 'responsable@aldea.edu.mx'],
            ['name' => 'Ing. Marco Ortiz', 'password' => Hash::make('password123'), 'email_verified_at' => now()],
        );
        $responsable->assignRole(Role::findOrCreate('responsable', 'web'));

        $actividades = [
            // tipo, aula, ponentes, fecha, inicio, fin, cupo propio, título, descripción
            ['Taller', 'Laboratorio 3 (Edificio C)', ['Mtro. Carlos Méndez'], '2026-11-24', '10:00', '12:30', null,
                'Taller de Ciberseguridad Defensiva en Nube', 'Implementación de arquitecturas Zero Trust, respuesta ante incidentes en entornos híbridos y mitigación de vulnerabilidades cloud nativas.'],
            ['Conferencia', 'Auditorio Principal', ['Dra. Ana García'], '2026-11-24', '12:00', '14:00', null,
                'IA Generativa en Educación Superior', 'Modelos de lenguaje multimodal, agentes autónomos y las nuevas fronteras de evaluación en el ecosistema universitario.'],
            ['Curso', 'Aula Magna Tech (Piso 2)', ['Ing. Sofía Morales', 'Mtro. Carlos Méndez'], '2026-11-25', '09:00', '13:00', null,
                'Arquitecturas Microservicios con Laravel & Docker', 'Contenedorización robusta, orquestación, colas distribuidas con Redis y contratos API desacoplados.'],
            ['Taller', 'Sala de Usos Múltiples', ['Ing. Roberto Valenzuela'], '2026-11-25', '15:00', '17:00', 2,
                'Análisis Forense Digital (cupo mínimo para probar)', 'Solo 2 lugares para probar el bloqueo por cupo lleno.'],
            ['Foro', 'Auditorio Principal', ['Dra. Ana García', 'Ing. Sofía Morales'], '2026-11-26', '16:30', '18:00', 50,
                'Tendencias del Mercado Tech 2027', 'Perspectivas de inserción global, contratación remota e impacto de nuevas regulaciones para egresados.'],
        ];

        foreach ($actividades as [$tipo, $aula, $nombres, $fecha, $inicio, $fin, $cupo, $titulo, $descripcion]) {
            $actividad = Activity::updateOrCreate(['title' => $titulo], [
                'description' => $descripcion,
                'activity_type_id' => $tipos[$tipo] ?? null,
                'room_id' => $aulas[$aula]->id,
                'responsible_id' => $responsable->id,
                'capacity' => $cupo,
                'date' => $fecha,
                'start_time' => $inicio,
                'end_time' => $fin,
            ]);
            $actividad->speakers()->sync(collect($nombres)->map(fn ($n) => $ponentes[$n]->id));
        }

        // Tutor académico (módulo de tutorados)
        $tutor = User::firstOrCreate(
            ['email' => 'tutor@aldea.edu.mx'],
            ['name' => 'Mtra. Laura Gómez', 'password' => Hash::make('password123'), 'email_verified_at' => now()],
        );
        $tutor->assignRole(Role::firstOrCreate(['name' => 'tutor']));

        // Compañeros inscritos al taller, para que la lista del responsable no esté vacía
        $taller = Activity::where('title', 'Taller de Ciberseguridad Defensiva en Nube')->first();
        $companeros = [
            ['Diego Hernández Ruiz', '202308117'],
            ['Mariana López Castillo', '202308255'],
            ['José Ángel Ramírez Soto', '202308301'],
        ];
        foreach ($companeros as $i => [$nombre, $matricula]) {
            $u = User::firstOrCreate(
                ['email' => "alumno{$i}@aldea.edu.mx"],
                ['name' => $nombre, 'password' => Hash::make('password123'), 'matricula' => $matricula,
                    'carrera' => 'Ingeniería en Software', 'email_verified_at' => now()],
            );
            $u->assignRole($rolAlumno);
            Enrollment::firstOrCreate(['user_id' => $u->id, 'activity_id' => $taller->id]);
        }

        // Actividad ya realizada (pre-evento) para probar las constancias de ponentes
        $previa = Activity::updateOrCreate(['title' => 'Pre-evento: Introducción a la Seguridad en la Nube'], [
            'description' => 'Charla previa al evento para alumnos de nuevo ingreso.',
            'activity_type_id' => $tipos['Conferencia'] ?? null,
            'room_id' => $aulas['Auditorio Principal']->id,
            'responsible_id' => $responsable->id,
            'date' => now()->subDays(7)->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:30',
            'duration_hours' => 2,
        ]);
        $previa->speakers()->sync([$ponentes['Mtro. Carlos Méndez']->id, $ponentes['Dra. Ana García']->id]);
        foreach (User::role('alumno')->where('email', '!=', 'alumno@aldea.edu.mx')->get() as $u) {
            Enrollment::firstOrCreate(
                ['user_id' => $u->id, 'activity_id' => $previa->id],
                ['attended' => true, 'checkin_time' => $previa->date->copy()->setTime(10, 5)],
            );
        }

        $this->command?->info('Alumno: alumno@aldea.edu.mx / password123');
        $this->command?->info('Responsable: responsable@aldea.edu.mx / password123');
        $this->command?->info('Tutor:  tutor@aldea.edu.mx / password123');
        $this->command?->info('Admin:  admin@aldea.edu.mx / password123');
    }
}
