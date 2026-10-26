<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'capacity'];

    // Relación: Un aula tiene muchas actividades
    public function activities()
    {
        return $this->hasMany(Activity::class);
    }
}