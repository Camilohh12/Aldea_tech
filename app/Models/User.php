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
    /** Dominio de los correos provisionales de tutores dados de alta sin correo real. */
    public const DOMINIO_CORREO_PENDIENTE = 'correo-pendiente.local';

    protected $fillable = [
        'name',
        'email',
        'password',
        'matricula',
        'carrera',
        'cuatrimestre',
        'grupo',
        'grupos_tutoria',
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

    /** Tutor con sus grupos al principio: «TIID_01_02, TIID_04_01 · Mayra Fabiola González». */
    public function etiquetaTutor(): string
    {
        return $this->grupos_tutoria ? "{$this->grupos_tutoria} · {$this->name}" : $this->name;
    }

    /** Tutor dado de alta sin correo real todavía (no puede iniciar sesión). */
    public function correoPendiente(): bool
    {
        return str_ends_with((string) $this->email, '@' . self::DOMINIO_CORREO_PENDIENTE);
    }
}
