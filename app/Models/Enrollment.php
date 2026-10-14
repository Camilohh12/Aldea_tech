<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Enrollment extends Model
{
    public const STATUS_REGISTERED = 'registered';
    public const STATUS_CANCELLED = 'cancelled';

    /** Bandera en memoria: el alumno ya tenía asistencia cuando se volvió a escanear. */
    public bool $wasAlreadyAttended = false;

    protected $fillable = [
        'user_id',
        'activity_id',
        'status',
        'qr_token',
        'attended',
        'checkin_time',
        'checked_in_by',
        'synced_offline',
    ];

    protected function casts(): array
    {
        return [
            'attended' => 'boolean',
            'synced_offline' => 'boolean',
            'checkin_time' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Cada inscripción nace con su código único para el pase QR.
        static::creating(function (Enrollment $enrollment) {
            $enrollment->qr_token ??= self::newToken();
        });
    }

    public static function newToken(): string
    {
        return Str::random(40);
    }

    /** Huella del código para validar sin conexión sin exponer el código real. */
    public function qrHash(): string
    {
        return hash('sha256', (string) $this->qr_token);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /** Tutor o personal que registró la asistencia. */
    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function scopeRegistered(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REGISTERED);
    }

    public function isRegistered(): bool
    {
        return $this->status === self::STATUS_REGISTERED;
    }
}
