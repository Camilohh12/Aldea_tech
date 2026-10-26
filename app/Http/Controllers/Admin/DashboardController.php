<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // Panel General: resumen del evento con datos reales
    public function index()
    {
        // Todas las actividades con sus inscritos y asistencias
        $activities = Activity::with(['activityType', 'room', 'responsible'])
            ->select('activities.*')
            ->withCount('speakers')
            ->selectSub($this->enrollmentsCount(), 'registered_count')
            ->selectSub($this->enrollmentsCount(attended: true), 'attended_count')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->each(fn (Activity $activity) => $activity->estado = $this->status($activity));

        $finished = $activities->where('estado', 'finalizada');
        $registeredInFinished = (int) $finished->sum('registered_count');
        $responsibleIds = $activities->pluck('responsible_id')->filter()->unique();

        // Tarjetas de resumen
        $stats = [
            'alumnosInscritos' => DB::table('enrollments')
                ->where('status', 'registered')
                ->distinct()
                ->count('user_id'),
            'alumnosRegistrados' => User::role('alumno')->count(),
            'lugares' => (int) $activities->sum('capacity'),
            'inscripciones' => (int) $activities->sum('registered_count'),
            'actividades' => $activities->count(),
            'enCurso' => $activities->where('estado', 'en_curso')->count(),
            'finalizadas' => $finished->count(),
            'responsables' => User::role('responsable')->count(),
            'responsablesConActividad' => User::role('responsable')->whereIn('id', $responsibleIds)->count(),
            'asistencias' => (int) $activities->sum('attended_count'),
            'horas' => round($activities->sum(fn (Activity $a) => $a->attended_count * $a->creditedHours()), 1),
            // Solo cuenta actividades que ya terminaron
            'tasaAsistencia' => $registeredInFinished > 0
                ? round($finished->sum('attended_count') * 100 / $registeredInFinished, 1)
                : null,
        ];

        return view('admin.dashboard', [
            'stats' => $stats,
            'eventStatus' => $this->eventStatus(),
            'days' => $this->days($activities),
            // Actividades más demandadas (inscritos / cupo)
            'demand' => $activities->filter(fn (Activity $a) => $a->capacity > 0)
                ->sortByDesc(fn (Activity $a) => [$a->registered_count / $a->capacity, $a->registered_count])
                ->take(5)
                ->values(),
            // Sesiones por supervisar: en curso y próximas
            'upcoming' => $activities->where('estado', '!=', 'finalizada')->take(8)->values(),
            'statusCounts' => $activities->countBy('estado'),
            'alerts' => [
                'sinResponsable' => $activities->whereNull('responsible_id')->count(),
                'sinPonentes' => $activities->where('speakers_count', 0)->count(),
                'llenas' => $activities->where('estado', 'llena')->count(),
            ],
        ]);
    }

    // Subconsulta: inscripciones activas (y, si se pide, con asistencia) por actividad
    private function enrollmentsCount(bool $attended = false)
    {
        return DB::table('enrollments')
            ->selectRaw('count(*)')
            ->whereColumn('enrollments.activity_id', 'activities.id')
            ->where('status', 'registered')
            ->when($attended, fn ($query) => $query->where('attended', true));
    }

    // Estado de la actividad según la hora actual y su cupo
    private function status(Activity $activity): string
    {
        if ($activity->hasEnded()) {
            return 'finalizada';
        }

        if ($activity->isInProgress()) {
            return 'en_curso';
        }

        if ($activity->capacity && $activity->registered_count >= $activity->capacity) {
            return 'llena';
        }

        return 'programada';
    }

    // Inscritos, asistencias y cupo de cada día del evento
    private function days($activities)
    {
        $period = CarbonPeriod::create(
            config('aldea.event.start_date'),
            config('aldea.event.end_date')
        );

        return collect($period)->map(function ($date) use ($activities) {
            $ofDay = $activities->filter(fn (Activity $a) => $a->date?->isSameDay($date));

            return [
                'label' => ucfirst($date->locale('es')->translatedFormat('l j \d\e F')),
                'actividades' => $ofDay->count(),
                'lugares' => (int) $ofDay->sum('capacity'),
                'inscritos' => (int) $ofDay->sum('registered_count'),
                'asistencias' => (int) $ofDay->sum('attended_count'),
            ];
        });
    }

    // Texto del estado general del evento (cuenta regresiva)
    private function eventStatus(): string
    {
        $start = Carbon::parse(config('aldea.event.start_date'))->startOfDay();
        $end = Carbon::parse(config('aldea.event.end_date'))->endOfDay();

        if (now()->lt($start)) {
            $days = (int) now()->startOfDay()->diffInDays($start);

            return $days === 1 ? 'Falta 1 día para el evento' : "Faltan {$days} días para el evento";
        }

        return now()->lte($end) ? 'Evento en curso' : 'Evento finalizado';
    }
}
