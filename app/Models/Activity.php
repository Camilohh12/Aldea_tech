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
        'capacity',
        'date',
        'start_time',
        'end_time',
        'checkin_enabled',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'capacity' => 'integer',
            'checkin_enabled' => 'boolean',
        ];
    }


    // ---------------------------------------------------------------- Relaciones

    public function type(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class, 'activity_type_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function speaker(): BelongsTo
    {
        return $this->belongsTo(Speaker::class);
    }

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

    /** Datos públicos de la actividad para respuestas JSON. */
    public function summary(): array
    {
        return [
            'title' => $this->title,
            'type' => $this->type?->name,
            'room' => $this->room?->name,
            'time' => $this->start_time ? substr($this->start_time, 0, 5) : null,
        ];
    }

    /**
     * Folio de la constancia del ponente, p. ej. AT26-P-00004-9F3A1C.
     * El sufijo se firma con APP_KEY para que no se pueda inventar un folio válido.
     */
    public function speakerCertificateFolio(): string
    {
        $firma = strtoupper(substr(hash_hmac('sha256', 'constancia-ponente:'.$this->id, (string) config('app.key')), 0, 6));

        return 'AT26-P-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT).'-'.$firma;
    }

    /** "24 Nov" (sin el punto que agrega Carbon en español). */
    public static function shortDate($date): ?string
    {
        if (! $date) {
            return null;
        }

        $texto = Carbon::parse($date)->locale('es')->isoFormat('D MMM');

        return \Illuminate\Support\Str::title(str_replace('.', '', $texto));
    }

    public function timeRange(): string
    {
        $start = $this->start_time ? substr($this->start_time, 0, 5) : '--:--';
        $end = $this->end_time ? substr($this->end_time, 0, 5) : '--:--';

        return "{$start} – {$end}";
    }
}
