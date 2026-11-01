<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Activity extends Model
{
    protected $fillable = [
        'title',
        'description',
        'activity_type_id',
        'room_id',
        'speaker_id',
        'responsible_id',
        'date',
        'start_time',
        'end_time',
        'capacity',
        'duration_hours',
        'checkin_enabled',
    ];

    protected $casts = [
        'date' => 'date',
        'capacity' => 'integer',
        'checkin_enabled' => 'boolean',
        'duration_hours' => 'decimal:2',
    ];

    // ---------------------------------------------------------------- Relaciones

    // Tipo de actividad: curso, taller, conferencia...
    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class);
    }

    // Aula o laboratorio asignado
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    // Responsable de registrar las asistencias (pase de lista)
    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    // Una actividad puede tener varios ponentes
    public function speakers(): BelongsToMany
    {
        return $this->belongsToMany(Speaker::class, 'activity_speaker')->withTimestamps();
    }

    // Inscripciones de los alumnos
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'enrollments')
            ->withPivot(['status', 'attended', 'checkin_time'])
            ->withTimestamps();
    }

    // ---------------------------------------------------------------- Scopes

    /** Agrega `registered_count` (inscripciones activas) a la consulta. */
    public function scopeWithRegisteredCount(Builder $query): Builder
    {
        return $query->withCount([
            'enrollments as registered_count' => fn ($q) => $q->where('status', Enrollment::STATUS_REGISTERED),
        ]);
    }

    public function scopeChronological(Builder $query): Builder
    {
        return $query->orderBy('date')->orderBy('start_time');
    }

    // ---------------------------------------------------------------- Ponentes

    /** "Mtro. Carlos Méndez y Dra. Ana García" (vacío si no hay ponentes). */
    public function speakerNames(): string
    {
        $nombres = $this->speakers->pluck('name');

        return $nombres->count() > 1
            ? $nombres->slice(0, -1)->implode(', ').' y '.$nombres->last()
            : (string) $nombres->first();
    }

    // ---------------------------------------------------------------- Cupo y horario

    /** Cupo de la actividad; si no tiene, el del aula. null = sin límite definido. */
    public function effectiveCapacity(): ?int
    {
        if ($this->capacity !== null) {
            return $this->capacity;
        }

        $roomCapacity = $this->room?->capacity;

        return $roomCapacity > 0 ? $roomCapacity : null;
    }

    public function registeredCount(): int
    {
        return $this->registered_count
            ?? $this->enrollments()->where('status', Enrollment::STATUS_REGISTERED)->count();
    }

    public function availableSpots(): ?int
    {
        $capacity = $this->effectiveCapacity();

        return $capacity === null ? null : max(0, $capacity - $this->registeredCount());
    }

    public function isFull(): bool
    {
        $available = $this->availableSpots();

        return $available !== null && $available <= 0;
    }

    // Horario para mostrar en pantalla, por ejemplo "10:00 – 12:00"
    public function timeRange(): string
    {
        $start = $this->start_time ? substr($this->start_time, 0, 5) : '--:--';
        $end = $this->end_time ? substr($this->end_time, 0, 5) : '--:--';

        return "{$start} – {$end}";
    }

    // Fecha y hora de inicio y fin, y estado según la hora actual
    public function startsAt(): ?Carbon
    {
        if (! $this->date || ! $this->start_time) {
            return null;
        }

        return Carbon::parse($this->date->format('Y-m-d').' '.$this->start_time);
    }

    public function endsAt(): ?Carbon
    {
        if (! $this->date || ! $this->end_time) {
            return null;
        }

        return Carbon::parse($this->date->format('Y-m-d').' '.$this->end_time);
    }

    public function hasStarted(): bool
    {
        $start = $this->startsAt();

        return $start !== null && $start->isPast();
    }

    public function isInProgress(): bool
    {
        $start = $this->startsAt();
        $end = $this->endsAt();

        return $start && $end && now()->between($start, $end);
    }

    public function hasEnded(): bool
    {
        $end = $this->endsAt();

        return $end !== null && $end->isPast();
    }

    public function durationMinutes(): int
    {
        $start = $this->startsAt();
        $end = $this->endsAt();

        return $start && $end ? (int) $start->diffInMinutes($end) : 0;
    }

    // Horas que acredita: las que fijó el admin o, si no hay, la duración del horario
    public function creditedHours(): float
    {
        return $this->duration_hours !== null
            ? (float) $this->duration_hours
            : round($this->durationMinutes() / 60, 2);
    }

    /** ¿Se cruza en horario con otra actividad? */
    public function overlapsWith(Activity $other): bool
    {
        if (! $this->date || ! $other->date || ! $this->date->isSameDay($other->date)) {
            return false;
        }

        if (! $this->start_time || ! $this->end_time || ! $other->start_time || ! $other->end_time) {
            return false;
        }

        return $this->start_time < $other->end_time && $other->start_time < $this->end_time;
    }

    /**
     * Ventana en la que un registro de asistencia es válido:
     * desde 15 min antes del inicio hasta 15 min después del final.
     */
    public function isWithinCheckinWindow(Carbon $moment): bool
    {
        $start = $this->startsAt();
        $end = $this->endsAt();

        if (! $start || ! $end) {
            return false;
        }

        return $moment->between($start->copy()->subMinutes(15), $end->copy()->addMinutes(15));
    }

    // ---------------------------------------------------------------- Pase de lista

    /** El responsable asignado y los administradores pueden pasar lista. */
    public function canTakeAttendance(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'super_admin'])
            || ($this->responsible_id !== null && $this->responsible_id === $user->id);
    }

    // ---------------------------------------------------------------- Presentación

    /** Datos públicos de la actividad para respuestas JSON. */
    public function summary(): array
    {
        return [
            'title' => $this->title,
            'type' => $this->activityType?->name,
            'room' => $this->room?->name,
            'time' => $this->start_time ? substr($this->start_time, 0, 5) : null,
        ];
    }

    /**
     * Folio de la constancia de un ponente en esta actividad, p. ej. AT26-P-00004-03-9F3A1C.
     * El sufijo se firma con APP_KEY para que no se pueda inventar un folio válido.
     */
    public function speakerCertificateFolio(Speaker $speaker): string
    {
        $firma = strtoupper(substr(hash_hmac(
            'sha256',
            'constancia-ponente:'.$this->id.':'.$speaker->id,
            (string) config('app.key'),
        ), 0, 6));

        return 'AT26-P-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT)
            .'-'.str_pad((string) $speaker->id, 2, '0', STR_PAD_LEFT).'-'.$firma;
    }

    /** "24 Nov" (sin el punto que agrega Carbon en español). */
    public static function shortDate($date): ?string
    {
        if (! $date) {
            return null;
        }

        $texto = Carbon::parse($date)->locale('es')->isoFormat('D MMM');

        return Str::title(str_replace('.', '', $texto));
    }
}
