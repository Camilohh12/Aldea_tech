<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Plantilla general de los certificados de ponentes (textos editables)
class CertificateTemplate extends Model
{
    protected $fillable = [
        'institution',
        'event_name',
        'heading',
        'title',
        'body',
        'signer1_name',
        'signer1_title',
        'signer2_name',
        'signer2_title',
        'footer',
    ];

    // Textos originales del certificado
    public const DEFAULTS = [
        'institution' => 'UNIVERSIDAD POLITÉCNICA DE PACHUCA',
        'event_name' => 'Aldea Tecnológica 2026',
        'heading' => 'OTORGA LA PRESENTE',
        'title' => 'CONSTANCIA',
        'body' => 'Por su valiosa participación como ponente en la actividad «{actividad}» ({tipo}), realizada el {fecha} de {horario} h en {aula}, en el marco de {evento}.',
        'signer1_name' => 'Coordinación de Aldea Tecnológica',
        'signer1_title' => 'Universidad Politécnica de Pachuca',
        'signer2_name' => null,
        'signer2_title' => null,
        'footer' => 'Pachuca de Soto, Hidalgo',
    ];

    // Datos que se llenan solos en el texto del certificado
    public const VARIABLES = [
        'ponente' => 'Nombre del ponente',
        'actividad' => 'Título de la actividad',
        'tipo' => 'Tipo (taller, curso…)',
        'fecha' => 'Fecha de la actividad',
        'horario' => 'Horario (10:00 a 12:00)',
        'aula' => 'Aula',
        'horas' => 'Horas acreditadas',
        'evento' => 'Nombre del evento',
    ];

    // La plantilla vigente (si no existe, se crea con los textos originales)
    public static function current(): self
    {
        return static::query()->first() ?? static::create(static::DEFAULTS);
    }

    // Valores de las variables para un ponente y una actividad
    public function valuesFor(Activity $activity, Speaker $speaker): array
    {
        return [
            'ponente' => $speaker->name,
            'actividad' => $activity->title,
            'tipo' => mb_strtolower($activity->activityType?->name ?? 'actividad'),
            'fecha' => $activity->date
                ? $activity->date->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY')
                : 'fecha por definir',
            'horario' => $activity->start_time && $activity->end_time
                ? substr($activity->start_time, 0, 5) . ' a ' . substr($activity->end_time, 0, 5)
                : 'horario por definir',
            'aula' => $activity->room?->name ?? 'aula por definir',
            'horas' => rtrim(rtrim(number_format($activity->creditedHours(), 1), '0'), '.'),
            'evento' => $this->event_name,
        ];
    }

    // Texto del certificado en HTML seguro: escapa todo y pone en negritas los datos
    public function renderBody(array $values): string
    {
        $html = e($this->body);

        foreach ($values as $key => $value) {
            $html = str_replace('{' . $key . '}', '<strong>' . e($value) . '</strong>', $html);
        }

        return $html;
    }
}
