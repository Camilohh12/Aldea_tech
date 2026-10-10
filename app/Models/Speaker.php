<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Speaker extends Model
{
    protected $fillable = ['name', 'matricula_id', 'bio'];

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }
}
