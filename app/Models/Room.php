<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = ['name', 'capacity'];

    protected function casts(): array
    {
        return ['capacity' => 'integer'];
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }
}
