<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    public const STATUS_REGISTERED = 'registered';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'activity_id',
        'status',
        'attended',
        'checkin_time',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
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
