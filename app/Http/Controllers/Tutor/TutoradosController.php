<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Enrollment;
use App\Models\User;
use App\Support\ProgresoTutorado;
use Illuminate\Http\Request;
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
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'in:'.implode(',', array_keys(ProgresoTutorado::ESTADOS))],
        ]);
        $q = trim($filtros['q'] ?? '');
        $estado = $filtros['estado'] ?? null;

        $todos = $this->progresos($request->user());

        // Búsqueda por nombre o matrícula y filtro por estado
        $busqueda = Str::lower(Str::ascii($q));
        $filtrados = $todos
            ->when($busqueda !== '', fn (Collection $c) => $c->filter(fn (ProgresoTutorado $p) => Str::contains(
                Str::lower(Str::ascii($p->alumno->name.' '.$p->alumno->matricula)),
                $busqueda,
            )))
            ->when($estado, fn (Collection $c) => $c->filter(fn (ProgresoTutorado $p) => $p->estado === $estado))
            ->values();

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

    /** @return Collection<int, ProgresoTutorado> */
    private function progresos(User $tutor): Collection
    {
        return User::role('alumno')
            ->where('tutor_id', $tutor->id)
            ->with(['enrollments.activity.activityType', 'enrollments.activity.room'])
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
