<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; // <-- 1. Importar esta línea

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles; // <-- 2. Agregar HasRoles aquí

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'matricula',
        'carrera',
        'cuatrimestre',
        'grupo',
        'estatus',
        'tutor_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** Actividades en las que el alumno tiene inscripción activa. */
    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'enrollments')
            ->wherePivot('status', Enrollment::STATUS_REGISTERED)
            ->withPivot(['status', 'attended', 'checkin_time'])
            ->withTimestamps();
    }

    /** Tutor académico asignado al alumno (users.tutor_id). */
    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    /** Alumnos que tiene asignados este tutor. */
    public function tutorados(): HasMany
    {
        return $this->hasMany(User::class, 'tutor_id');
    }
}
