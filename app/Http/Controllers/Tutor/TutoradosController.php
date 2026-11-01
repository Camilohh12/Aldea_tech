<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Enrollment;
use App\Models\User;
use App\Support\ProgresoTutorado;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Supervisión y seguimiento de tutorados (solo lectura).
 * El tutor ve únicamente a los alumnos con users.tutor_id = su id.
 */
class TutoradosController extends Controller
{
    public function index(Request $request): View
    {
        [$q, $estado] = $this->filtros($request);

        $todos = $this->progresos($request->user());
        $filtrados = $this->filtrar($todos, $q, $estado);

        return view('tutor.index', [
            'q' => $q,
            'estado' => $estado,
            'tutorados' => $filtrados,
            'conteos' => collect(ProgresoTutorado::ESTADOS)
                ->map(fn ($etiqueta, $clave) => $todos->where('estado', $clave)->count())
                ->put('todos', $todos->count()),
            'indicadores' => [
                'asignados' => $todos->count(),
                'asistencias' => $todos->sum('asistencias'),
                'pendientes' => $todos->where('estado', ProgresoTutorado::PENDIENTE)->count(),
                'inscripciones' => $todos->sum('inscripcionesActivas'),
            ],
            'porCarrera' => $this->resumenPorCarrera($todos),
            'proximas' => $this->proximasActividades($todos),
            'meta' => (float) config('aldea.horas_liberacion', 20),
        ]);
    }

    public function show(Request $request, User $alumno): View
    {
        // Solo sus tutorados; para cualquier otro alumno la página "no existe"
        abort_unless($alumno->tutor_id === $request->user()->id && $alumno->hasRole('alumno'), 404);

        $alumno->load(['enrollments.activity.activityType', 'enrollments.activity.room', 'enrollments.activity.speakers']);

        return view('tutor.show', [
            'progreso' => new ProgresoTutorado($alumno),
        ]);
    }

    /** Reporte de asistencia de los tutorados en PDF (respeta la búsqueda y el estado elegidos). */
    public function reporte(Request $request): Response
    {
        [$q, $estado] = $this->filtros($request);
        $tutor = $request->user();

        $todos = $this->progresos($tutor, conDetalle: true);
        $tutorados = $this->filtrar($todos, $q, $estado);
        $escudo = public_path('img/escudo-upp.png');

        $pdf = Pdf::loadView('pdf.reporte-tutorados', [
            'tutor' => $tutor,
            'tutorados' => $tutorados,
            'filtro' => collect([$estado ? ProgresoTutorado::ESTADOS[$estado] : null, $q !== '' ? "«{$q}»" : null])->filter()->implode(' · '),
            'resumen' => collect(ProgresoTutorado::ESTADOS)->map(fn ($etiqueta, $clave) => $tutorados->where('estado', $clave)->count()),
            'meta' => (float) config('aldea.horas_liberacion', 20),
            'generado' => now(),
            // DomPDF necesita la extensión GD para las imágenes; sin ella el reporte sale sin escudo
            'escudo' => extension_loaded('gd') && is_file($escudo)
                ? 'data:image/png;base64,'.base64_encode(file_get_contents($escudo))
                : null,
        ])
            ->setPaper('letter')
            ->setOption('isFontSubsettingEnabled', true);

        // Número de página en cada hoja, a la derecha del pie y con su mismo tamaño (7px = 5.25pt)
        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $canvas = $dompdf->getCanvas();
        $fuente = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $ancho = $dompdf->getFontMetrics()->getTextWidth('Página 00 de 00', $fuente, 5.25);
        $canvas->page_text($canvas->get_width() - 28.5 - $ancho, 770, 'Página {PAGE_NUM} de {PAGE_COUNT}',
            $fuente, 5.25, [0.42, 0.42, 0.5]);

        $archivo = 'Reporte-tutorados-'.Str::slug($tutor->name).'-'.now()->format('Y-m-d').'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$archivo.'"',
        ]);
    }

    /** @return array{0: string, 1: ?string} Búsqueda y estado de la URL */
    private function filtros(Request $request): array
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'in:'.implode(',', array_keys(ProgresoTutorado::ESTADOS))],
        ]);

        return [trim($filtros['q'] ?? ''), $filtros['estado'] ?? null];
    }

    /** Búsqueda por nombre o matrícula y filtro por estado. */
    private function filtrar(Collection $todos, string $q, ?string $estado): Collection
    {
        $busqueda = Str::lower(Str::ascii($q));

        return $todos
            ->when($busqueda !== '', fn (Collection $c) => $c->filter(fn (ProgresoTutorado $p) => Str::contains(
                Str::lower(Str::ascii($p->alumno->name.' '.$p->alumno->matricula)),
                $busqueda,
            )))
            ->when($estado, fn (Collection $c) => $c->filter(fn (ProgresoTutorado $p) => $p->estado === $estado))
            ->values();
    }

    /** @return Collection<int, ProgresoTutorado> */
    private function progresos(User $tutor, bool $conDetalle = false): Collection
    {
        return User::role('alumno')
            ->where('tutor_id', $tutor->id)
            ->with(['enrollments.activity.activityType', 'enrollments.activity.room'])
            ->when($conDetalle, fn ($query) => $query->with('enrollments.checkedInBy'))
            ->orderBy('name')
            ->get()
            ->map(fn (User $alumno) => new ProgresoTutorado($alumno));
    }

    /** Horas acumuladas contra la meta, agrupadas por carrera. */
    private function resumenPorCarrera(Collection $todos): Collection
    {
        return $todos
            ->groupBy(fn (ProgresoTutorado $p) => $p->alumno->carrera ?: 'Sin carrera registrada')
            ->map(function (Collection $grupo, string $carrera) {
                $horas = round($grupo->sum('horas'), 1);
                $meta = $grupo->sum('meta');

                return [
                    'carrera' => $carrera,
                    'alumnos' => $grupo->count(),
                    'horas' => $horas,
                    'meta' => $meta,
                    'porcentaje' => $meta > 0 ? (int) min(100, round($horas / $meta * 100)) : 0,
                ];
            })
            ->sortByDesc('alumnos')
            ->values();
    }

    /** Las 3 próximas actividades donde hay tutorados inscritos. */
    private function proximasActividades(Collection $todos): Collection
    {
        return $todos
            ->flatMap(fn (ProgresoTutorado $p) => $p->inscripciones->filter(
                fn (Enrollment $e) => $e->isRegistered() && $e->activity && ! $e->activity->hasEnded()
            ))
            ->groupBy('activity_id')
            ->map(fn (Collection $grupo) => [
                'actividad' => $grupo->first()->activity,
                'tutorados' => $grupo->count(),
            ])
            ->sortBy(fn (array $item) => $item['actividad']->startsAt()?->timestamp ?? PHP_INT_MAX)
            ->take(3)
            ->values();
    }
}
