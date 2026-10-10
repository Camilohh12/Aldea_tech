<?php

namespace App\Http\Controllers\Alumno;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityType;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogoController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'tipo' => ['nullable', 'integer'],
            'fecha' => ['nullable', 'date'],
        ]);

        $activities = Activity::query()
            ->with(['type', 'room', 'speaker'])
            ->withRegisteredCount()
            ->when($filters['q'] ?? null, function ($query, $q) {
                $query->where(fn ($sub) => $sub
                    ->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhereHas('speaker', fn ($s) => $s->where('name', 'like', "%{$q}%")));
            })
            ->when($filters['tipo'] ?? null, fn ($query, $tipo) => $query->where('activity_type_id', $tipo))
            ->when($filters['fecha'] ?? null, fn ($query, $fecha) => $query->whereDate('date', $fecha))
            ->chronological()
            ->get();

        // activity_id => attended (solo inscripciones activas)
        $misInscripciones = $request->user()->enrollments()
            ->where('status', \App\Models\Enrollment::STATUS_REGISTERED)
            ->pluck('attended', 'activity_id');

        $dates = Activity::query()
            ->whereNotNull('date')
            ->orderBy('date')
            ->pluck('date')
            ->map->format('Y-m-d')
            ->unique()
            ->values();

        return view('alumno.catalogo', [
            'activities' => $activities,
            'misInscripciones' => $misInscripciones,
            'types' => ActivityType::orderBy('name')->get(),
            'dates' => $dates,
            'filters' => $filters,
        ]);
    }
}
