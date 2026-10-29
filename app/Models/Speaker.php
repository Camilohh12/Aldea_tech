<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Speaker extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'matricula_id', 'bio'];

    // Relación: Un ponente participa en muchas actividades
    // (tabla activity_speaker, la misma que usa Activity::speakers())
    public function activities()
    {
        return $this->belongsToMany(
            Activity::class,
            'activity_speaker'
        )->withTimestamps();
    }

    // Iniciales para el avatar, sin títulos como "Dr." o "Mtra."
    protected function initials(): Attribute
    {
        return Attribute::get(function () {
            $words = collect(preg_split('/\s+/', trim($this->name)))
                ->reject(fn ($word) => preg_match('/^(dr|dra|mtro|mtra|ing|lic|prof|profa)\.?$/i', $word))
                ->values();

            return mb_strtoupper(
                mb_substr($words[0] ?? '', 0, 1) . mb_substr($words[1] ?? '', 0, 1)
            );
        });
    }
}
