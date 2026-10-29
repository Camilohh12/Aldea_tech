<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Enrollment;
use App\Models\Speaker;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Certificados: el administrador genera la constancia de cada ponente.
 * Una actividad puede tener varios ponentes (activity_speaker), así que hay
 * una constancia por cada par actividad–ponente, cuando la actividad terminó.
 */
class ConstanciasPonentesController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $actividades = Activity::with(['activityType', 'room', 'speakers'])
            ->withCount(['enrollments as asistentes' => fn ($query) => $query
                ->where('status', Enrollment::STATUS_REGISTERED)
                ->where('attended', true)])
            ->when($q !== '', fn ($query) => $query->where(fn ($sub) => $sub
                ->where('title', 'like', "%{$q}%")
                ->orWhereHas('speakers', fn ($s) => $s->where('name', 'like', "%{$q}%"))))
            ->get()
            ->sortByDesc(fn (Activity $a) => $a->startsAt()?->timestamp ?? 0)
            ->values();

        $conPonentes = $actividades->filter(fn (Activity $a) => $a->speakers->isNotEmpty());
        $listas = $conPonentes->filter(fn (Activity $a) => $a->hasEnded())->values();

        return view('admin.constancias-ponentes', [
            'q' => $q,
            'listas' => $listas,
            'proximas' => $conPonentes->reject(fn (Activity $a) => $a->hasEnded())->values(),
            'sinPonente' => $actividades->filter(fn (Activity $a) => $a->speakers->isEmpty())->values(),
            'totalConstancias' => $listas->sum(fn (Activity $a) => $a->speakers->count()),
        ]);
    }

    public function download(Activity $activity, Speaker $speaker): Response
    {
        // El ponente debe estar asignado a esta actividad
        abort_unless($activity->speakers()->whereKey($speaker->id)->exists(), 404, 'El ponente no participa en esta actividad.');
        abort_unless($activity->hasEnded(), 403, 'La constancia se genera cuando termina la actividad.');

        $activity->load(['activityType', 'room']);

        $asistentes = $activity->enrollments()
            ->where('status', Enrollment::STATUS_REGISTERED)
            ->where('attended', true)
            ->count();

        $escudo = public_path('img/escudo-upp.png');

        $pdf = Pdf::loadView('pdf.constancia-ponente', [
            'actividad' => $activity,
            'ponente' => $speaker,
            'asistentes' => $asistentes,
            'folio' => $activity->speakerCertificateFolio($speaker),
            'escudo' => is_file($escudo) ? 'data:image/png;base64,'.base64_encode(file_get_contents($escudo)) : null,
        ])
            ->setPaper('letter', 'landscape')
            ->setOption('isFontSubsettingEnabled', true); // PDF más ligero

        $archivo = 'Constancia-'.Str::slug($speaker->name).'-'.Str::slug(Str::limit($activity->title, 40, '')).'.pdf';

        return $pdf->download($archivo);
    }
}
