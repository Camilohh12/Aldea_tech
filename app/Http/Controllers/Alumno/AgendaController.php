<?php

namespace App\Http\Controllers\Alumno;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgendaController extends Controller
{
    public function index(Request $request): View
    {
        $filtro = $request->validate(['fecha' => ['nullable', 'date']])['fecha'] ?? null;

        $enrollments = Enrollment::registered()
            ->where('user_id', $request->user()->id)
            ->with(['activity.type', 'activity.room', 'activity.speaker'])
            ->get()
            ->sortBy(fn (Enrollment $e) => $e->activity->startsAt()?->timestamp ?? PHP_INT_MAX)
            ->values();

        // Días del evento (todos, aunque el alumno no tenga actividades ese día).
        $eventDays = Activity::whereNotNull('date')->orderBy('date')->pluck('date')
            ->map->format('Y-m-d')->unique()->values();

        $byDay = $enrollments->groupBy(fn (Enrollment $e) => $e->activity->date?->format('Y-m-d') ?? 'sin-fecha');

        $days = $eventDays
            ->merge($byDay->keys())
            ->unique()
            ->when($filtro, fn ($c) => $c->filter(fn ($d) => $d === $filtro))
            ->values();

        $minutosAcreditados = $enrollments->where('attended', true)
            ->sum(fn (Enrollment $e) => $e->activity->durationMinutes());

        $hoy = now()->format('Y-m-d');

        return view('alumno.agenda', [
            'days' => $days,
            'eventDays' => $eventDays,
            'byDay' => $byDay,
            'filtro' => $filtro,
            'total' => $enrollments->count(),
            'attended' => $enrollments->where('attended', true)->count(),
            'horasAcreditadas' => round($minutosAcreditados / 60, 1),
            'horasMeta' => config('aldea.horas_liberacion'),
            'tieneActividadHoy' => $enrollments->contains(fn (Enrollment $e) => $e->activity->date?->format('Y-m-d') === $hoy && ! $e->attended),
        ]);
    }
}
