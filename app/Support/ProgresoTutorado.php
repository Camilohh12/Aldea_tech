<?php

namespace App\Support;

use App\Models\Activity;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Progreso de un alumno hacia la liberación curricular.
 *
 * Reglas:
 * - Solo cuentan las inscripciones activas (status = registered) con asistencia
 *   validada (attended = true).
 * - Las horas de cada actividad son Activity::creditedHours() (duration_hours o,
 *   si no se capturó, la duración del horario).
 * - Estado: Acreditado si llega a la meta (config aldea.horas_liberacion),
 *   En progreso si tiene horas pero no llega, Pendiente si aún no tiene horas.
 */
class ProgresoTutorado
{
    public const ACREDITADO = 'acreditado';
    public const EN_PROGRESO = 'en_progreso';
    public const PENDIENTE = 'pendiente';

    public const ESTADOS = [
        self::ACREDITADO => 'Acreditado',
        self::EN_PROGRESO => 'En progreso',
        self::PENDIENTE => 'Pendiente',
    ];

    public readonly float $horas;
    public readonly float $meta;
    public readonly int $porcentaje;
    public readonly string $estado;
    public readonly int $asistencias;
    public readonly int $inscripcionesActivas;
    public readonly ?Activity $actividadDestacada;

    /** @var Collection<int, Enrollment> */
    public readonly Collection $inscripciones;

    public function __construct(public readonly User $alumno)
    {
        $this->meta = (float) config('aldea.horas_liberacion', 20);

        // Requiere enrollments.activity cargados para no hacer consultas por alumno
        $this->inscripciones = $alumno->enrollments
            ->sortBy(fn (Enrollment $e) => $e->activity?->startsAt()?->timestamp ?? PHP_INT_MAX)
            ->values();

        $activas = $this->inscripciones->filter(fn (Enrollment $e) => $e->isRegistered());
        $validadas = $activas->filter(fn (Enrollment $e) => $e->attended);

        $this->inscripcionesActivas = $activas->count();
        $this->asistencias = $validadas->count();
        $this->horas = round($validadas->sum(fn (Enrollment $e) => $e->activity->creditedHours()), 1);
        $this->porcentaje = $this->meta > 0 ? (int) min(100, round($this->horas / $this->meta * 100)) : 0;

        $this->estado = match (true) {
            $this->horas >= $this->meta => self::ACREDITADO,
            $this->horas > 0 => self::EN_PROGRESO,
            default => self::PENDIENTE,
        };

        // Para la tabla: la próxima actividad inscrita o, si no hay, la última asistida
        $this->actividadDestacada = $activas
            ->first(fn (Enrollment $e) => ! $e->activity->hasEnded())?->activity
            ?? $validadas->last()?->activity;
    }

    public function etiqueta(): string
    {
        return self::ESTADOS[$this->estado];
    }
}
