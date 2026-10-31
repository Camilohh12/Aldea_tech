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
                    'qr_token' => Enrollment::newToken(), // el pase anterior deja de servir
                    'attended' => false,
                    'checkin_time' => null,
                    'checked_in_by' => null,
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
     * El tutor escanea el pase QR de un alumno y registra su asistencia.
     *
     * @param  User  $staff  Tutor/ponente que escanea.
     * @param  Activity  $activity  Actividad en la que se está pasando lista.
     * @param  string  $token  Código leído del QR del alumno.
     * @param  Carbon|null  $scannedAt  Hora del escaneo si se guardó sin conexión; null = ahora.
     */
    public function checkIn(User $staff, Activity $activity, string $token, ?Carbon $scannedAt = null): Enrollment
    {
        $enrollment = Enrollment::with(['user', 'activity'])->where('qr_token', $token)->first();

        if (! $enrollment) {
            throw new EnrollmentException('El código no corresponde a ningún pase de Aldea Tecnológica.');
        }

        if ($enrollment->activity_id !== $activity->id) {
            throw new EnrollmentException(
                "Este pase es de «{$enrollment->activity->title}», no de esta actividad.",
            );
        }

        if (! $enrollment->isRegistered()) {
            throw new EnrollmentException("{$enrollment->user->name} canceló su inscripción a esta actividad.");
        }

        return $this->markAttended($staff, $enrollment, $scannedAt);
    }

    /** Registro manual desde la lista (por ejemplo, si al alumno se le apagó el celular). */
    public function checkInManual(User $staff, Activity $activity, int $enrollmentId): Enrollment
    {
        $enrollment = Enrollment::registered()
            ->with(['user', 'activity'])
            ->where('activity_id', $activity->id)
            ->find($enrollmentId);

        if (! $enrollment) {
            throw new EnrollmentException('Ese alumno no está inscrito en esta actividad.');
        }

        return $this->markAttended($staff, $enrollment);
    }

    private function markAttended(User $staff, Enrollment $enrollment, ?Carbon $scannedAt = null): Enrollment
    {
        $activity = $enrollment->activity;
        $offline = $scannedAt !== null;

        if ($enrollment->attended) {
            $enrollment->wasAlreadyAttended = true; // Escanear dos veces no es error.

            return $enrollment;
        }

        if ($offline) {
            // No sabemos si el pase de lista estaba abierto al escanear: validamos
            // que la hora del escaneo caiga dentro del horario de la actividad.
            if ($scannedAt->isFuture() || ! $activity->isWithinCheckinWindow($scannedAt)) {
                throw new EnrollmentException(
                    "El registro de {$enrollment->user->name} guardado sin conexión está fuera del horario de la actividad.",
                );
            }
        } elseif (! $activity->checkin_enabled) {
            throw new EnrollmentException('El pase de lista de esta actividad está cerrado. Ábrelo para registrar asistencias.');
        }

        $enrollment->update([
            'attended' => true,
            'checkin_time' => $scannedAt ?? now(),
            'checked_in_by' => $staff->id,
            'synced_offline' => $offline,
        ]);

        return $enrollment;
    }
}
