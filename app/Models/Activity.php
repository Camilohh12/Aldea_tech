<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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
        'checkin_enabled' => 'boolean',
        'duration_hours' => 'decimal:2',
    ];

    // Tipo de actividad: curso, taller, conferencia...
    public function activityType()
    {
        return $this->belongsTo(ActivityType::class);
    }

    // Aula o laboratorio asignado
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // Responsable de registrar las asistencias
    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    // Una actividad puede tener varios ponentes
    public function speakers()
    {
        return $this->belongsToMany(
            Speaker::class,
            'activity_speaker'
        )->withTimestamps();
    }

    // Inscripciones de los alumnos
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    // Horario para mostrar en pantalla, por ejemplo "10:00 – 12:00"
    // (mismo método que usa el módulo alumno)
    public function timeRange(): string
    {
        $start = $this->start_time ? substr($this->start_time, 0, 5) : '--:--';
        $end = $this->end_time ? substr($this->end_time, 0, 5) : '--:--';

        return "{$start} – {$end}";
    }

    // Fecha y hora de inicio y fin, y estado según la hora actual
    // (mismos métodos que usa el módulo alumno)
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
}