<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityType;
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
                'carrera' => 'Ing. en Software',
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

        $actividades = [
            // tipo, aula, ponente, fecha, inicio, fin, cupo propio, título, descripción
            ['Taller', 'Laboratorio 3 (Edificio C)', 'Mtro. Carlos Méndez', '2026-11-24', '10:00', '12:30', null,
                'Taller de Ciberseguridad Defensiva en Nube', 'Implementación de arquitecturas Zero Trust, respuesta ante incidentes en entornos híbridos y mitigación de vulnerabilidades cloud nativas.'],
            ['Conferencia', 'Auditorio Principal', 'Dra. Ana García', '2026-11-24', '12:00', '14:00', null,
                'IA Generativa en Educación Superior', 'Modelos de lenguaje multimodal, agentes autónomos y las nuevas fronteras de evaluación en el ecosistema universitario.'],
            ['Curso', 'Aula Magna Tech (Piso 2)', 'Ing. Sofía Morales', '2026-11-25', '09:00', '13:00', null,
                'Arquitecturas Microservicios con Laravel & Docker', 'Contenedorización robusta, orquestación, colas distribuidas con Redis y contratos API desacoplados.'],
            ['Taller', 'Sala de Usos Múltiples', 'Ing. Roberto Valenzuela', '2026-11-25', '15:00', '17:00', 2,
                'Análisis Forense Digital (cupo mínimo para probar)', 'Solo 2 lugares para probar el bloqueo por cupo lleno.'],
            ['Foro', 'Auditorio Principal', 'Dra. Ana García', '2026-11-26', '16:30', '18:00', 50,
                'Tendencias del Mercado Tech 2027', 'Perspectivas de inserción global, contratación remota e impacto de nuevas regulaciones para egresados.'],
        ];

        foreach ($actividades as [$tipo, $aula, $ponente, $fecha, $inicio, $fin, $cupo, $titulo, $descripcion]) {
            Activity::updateOrCreate(['title' => $titulo], [
                'description' => $descripcion,
                'activity_type_id' => $tipos[$tipo] ?? null,
                'room_id' => $aulas[$aula]->id,
                'speaker_id' => $ponentes[$ponente]->id,
                'capacity' => $cupo,
                'date' => $fecha,
                'start_time' => $inicio,
                'end_time' => $fin,
            ]);
        }

        $this->command?->info('Alumno de prueba: alumno@aldea.edu.mx / password123');
        $this->command?->table(
            ['Actividad', 'Código para el QR (checkin_token)'],
            Activity::orderBy('date')->orderBy('start_time')->get()
                ->map(fn ($a) => [$a->title, $a->checkin_token])->all(),
        );
    }
}
