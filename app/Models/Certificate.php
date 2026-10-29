<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Certificado emitido a un ponente por una actividad
class Certificate extends Model
{
    protected $fillable = [
        'activity_id',
        'speaker_id',
        'folio',
        'issued_by',
        'issued_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function speaker()
    {
        return $this->belongsTo(Speaker::class);
    }

    // Administrador que lo emitió
    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    // Folio único, p. ej. AT26-P-00004-007-9F3A1C.
    // El final se firma con APP_KEY para que no se pueda inventar un folio válido.
    public static function folioFor(Activity $activity, Speaker $speaker): string
    {
        $firma = strtoupper(substr(
            hash_hmac('sha256', 'certificado-ponente:' . $activity->id . ':' . $speaker->id, (string) config('app.key')),
            0,
            6
        ));

        return 'AT26-P-'
            . str_pad((string) $activity->id, 5, '0', STR_PAD_LEFT) . '-'
            . str_pad((string) $speaker->id, 3, '0', STR_PAD_LEFT) . '-'
            . $firma;
    }
}
