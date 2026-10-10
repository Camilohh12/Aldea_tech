<?php

namespace App\Services;

use App\Exceptions\EnrollmentException;
use App\Models\Activity;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Reglas de negocio del módulo Alumno: inscripción con control de cupo,
 * cancelación y registro de asistencia por QR (en línea y sincronizada).
 */
class EnrollmentService
{
    /**
     * Inscribe al alumno validando cupo, duplicados, horario y choques.
     *
     * Todo ocurre dentro de una transacción con la fila de la actividad
     * bloqueada (lockForUpdate), así dos alumnos no pueden ocupar el mismo
     * último lugar. En MySQL el bloqueo es real; en SQLite toda la base se
     * bloquea al escribir, lo que también evita la sobreventa.
     */
    public function enroll(User $student, int $activityId): Enrollment
    {
        return DB::transaction(function () use ($student, $activityId) {
            /** @var Activity $activity */
            $activity = Activity::with('room')->lockForUpdate()->findOrFail($activityId);

            if ($activity->hasStarted()) {
                throw new EnrollmentException('Esta actividad ya comenzó; la inscripción está cerrada.', EnrollmentException::CERRADA);
            }

            $existing = Enrollment::where('user_id', $student->id)
                ->where('activity_id', $activity->id)
                ->first();

            if ($existing?->isRegistered()) {
                throw new EnrollmentException('Ya estás inscrito en esta actividad.', EnrollmentException::DUPLICADO);
            }

            $registered = Enrollment::registered()->where('activity_id', $activity->id)->count();
            $capacity = $activity->effectiveCapacity();

            if ($capacity !== null && $registered >= $capacity) {
                throw new EnrollmentException('El cupo de esta actividad está lleno.', EnrollmentException::CUPO);
            }

            $clash = $student->activities()
                ->whereDate('activities.date', $activity->date)
                ->get()
                ->first(fn (Activity $other) => $other->overlapsWith($activity));

            if ($clash) {
                throw new EnrollmentException(
                    "Se cruza con «{$clash->title}» ({$clash->timeRange()}), en el que ya estás inscrito.",
                    EnrollmentException::CONFLICTO,
                );
            }

            // Si había cancelado antes, reactivamos su registro (el índice único
            // no permite crear otro).
            if ($existing) {
                $existing->update([
                    'status' => Enrollment::STATUS_REGISTERED,
                    'attended' => false,
                    'checkin_time' => null,
                    'synced_offline' => false,
                ]);

                return $existing;
            }

            return Enrollment::create([
                'user_id' => $student->id,
                'activity_id' => $activity->id,
                'status' => Enrollment::STATUS_REGISTERED,
            ]);
        });
    }

    /** Cancela la inscripción y libera el lugar. */
    public function cancel(User $student, int $activityId): void
    {
        $enrollment = Enrollment::registered()
            ->with('activity')
            ->where('user_id', $student->id)
            ->where('activity_id', $activityId)
            ->first();

        if (! $enrollment) {
            throw new EnrollmentException('No estás inscrito en esta actividad.');
        }

        if ($enrollment->attended || $enrollment->activity->hasStarted()) {
            throw new EnrollmentException('Ya no puedes cancelar: la actividad comenzó o ya registraste asistencia.');
        }

        $enrollment->update(['status' => Enrollment::STATUS_CANCELLED]);
    }

    /**
     * Registra la asistencia del alumno a partir del código escaneado.
     *
     * @param  Carbon|null  $scannedAt  Momento en que se escaneó sin conexión.
     *                                  null = escaneo en línea (ahora).
     */
    public function checkIn(User $student, string $token, ?Carbon $scannedAt = null): Enrollment
    {
        $offline = $scannedAt !== null;

        $activity = Activity::where('checkin_token', $token)->first();

        if (! $activity) {
            throw new EnrollmentException('El código QR no corresponde a ninguna actividad.');
        }

        $enrollment = Enrollment::registered()
            ->where('user_id', $student->id)
            ->where('activity_id', $activity->id)
            ->first();

        if (! $enrollment) {
            throw new EnrollmentException("No estás inscrito en «{$activity->title}».");
        }

        $enrollment->setRelation('activity', $activity->loadMissing(['type', 'room']));

        if ($enrollment->attended) {
            return $enrollment; // Escanear dos veces no es error.
        }

        if ($offline) {
            // No sabemos si el switch estaba activo al escanear: validamos que
            // la hora del escaneo caiga dentro del horario de la actividad.
            if ($scannedAt->isFuture() || ! $activity->isWithinCheckinWindow($scannedAt)) {
                throw new EnrollmentException("El registro guardado sin conexión está fuera del horario de «{$activity->title}».");
            }
        } elseif (! $activity->checkin_enabled) {
            throw new EnrollmentException("El pase de lista de «{$activity->title}» aún no está habilitado.");
        }

        $enrollment->update([
            'attended' => true,
            'checkin_time' => $scannedAt ?? now(),
            'synced_offline' => $offline,
        ]);

        return $enrollment;
    }
}
