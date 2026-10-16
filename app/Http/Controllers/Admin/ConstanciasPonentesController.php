<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Enrollment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * El administrador genera las constancias de los ponentes:
 * una por cada actividad terminada que tenga ponente asignado.
 */
class ConstanciasPonentesController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $actividades = Activity::with(['type', 'room', 'speaker'])
            ->withCount(['enrollments as asistentes' => fn ($query) => $query
                ->where('status', Enrollment::STATUS_REGISTERED)
                ->where('attended', true)])
            ->when($q !== '', fn ($query) => $query->where(fn ($sub) => $sub
                ->where('title', 'like', "%{$q}%")
                ->orWhereHas('speaker', fn ($s) => $s->where('name', 'like', "%{$q}%"))))
            ->get()
            ->sortByDesc(fn (Activity $a) => $a->startsAt()?->timestamp ?? 0)
            ->values();

        $conPonente = $actividades->filter(fn (Activity $a) => $a->speaker);

        return view('admin.constancias-ponentes', [
            'q' => $q,
            'listas' => $conPonente->filter(fn (Activity $a) => $a->hasEnded())->values(),
            'proximas' => $conPonente->reject(fn (Activity $a) => $a->hasEnded())->values(),
            'sinPonente' => $actividades->reject(fn (Activity $a) => $a->speaker)->values(),
        ]);
    }

    public function download(Activity $activity): Response
    {
        $activity->load(['type', 'room', 'speaker']);

        abort_unless($activity->speaker, 404, 'La actividad no tiene ponente asignado.');
        abort_unless($activity->hasEnded(), 403, 'La constancia se genera cuando termina la actividad.');

        $asistentes = $activity->enrollments()
            ->where('status', Enrollment::STATUS_REGISTERED)
            ->where('attended', true)
            ->count();

        $escudo = public_path('img/escudo-upp.png');

        $pdf = Pdf::loadView('pdf.constancia-ponente', [
            'actividad' => $activity,
            'ponente' => $activity->speaker,
            'asistentes' => $asistentes,
            'folio' => $activity->speakerCertificateFolio(),
            'escudo' => is_file($escudo) ? 'data:image/png;base64,'.base64_encode(file_get_contents($escudo)) : null,
        ])
            ->setPaper('letter', 'landscape')
            ->setOption('isFontSubsettingEnabled', true); // PDF más ligero

        $archivo = 'Constancia-'.Str::slug($activity->speaker->name).'-'.Str::slug(Str::limit($activity->title, 40, '')).'.pdf';

        return $pdf->download($archivo);
    }
}
