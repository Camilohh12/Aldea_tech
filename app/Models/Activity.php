<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

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
    protected function timeRange(): Attribute
    {
        return Attribute::get(
            fn () => substr((string) $this->start_time, 0, 5) . ' – ' . substr((string) $this->end_time, 0, 5)
        );
    }
}