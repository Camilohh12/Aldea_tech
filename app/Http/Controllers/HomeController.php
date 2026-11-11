<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LeeFiltros;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Speaker;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// Página principal pública: portada del evento y catálogo de actividades
class HomeController extends Controller
{
    use LeeFiltros;

    public function index(Request $request)
    {
        $typeId = $this->filtroEntero($request, 'tipo');
        $day = $this->filtroFecha($request, 'dia');

        // Actividades que todavía no terminan, con sus inscritos
        $upcoming = Activity::with(['activityType', 'room', 'speakers'])
            ->select('activities.*')
            ->selectSub(
                DB::table('enrollments')
                    ->selectRaw('count(*)')
                    ->whereColumn('enrollments.activity_id', 'activities.id')
                    ->where('status', 'registered'),
                'registered_count'
            )
            ->whereNotNull('date')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->reject(fn (Activity $activity) => $activity->hasEnded())
            ->values();

        // Catálogo con los filtros de tipo y día
        $catalog = $upcoming
            ->when($typeId, fn ($c) => $c->where('activity_type_id', (int) $typeId))
            ->when($day, fn ($c) => $c->filter(fn (Activity $a) => $a->date->format('Y-m-d') === $day))
            ->values();

        $all = Activity::all();

        return view('welcome', [
            'catalog' => $catalog,
            'featured' => $upcoming->first(),
            'typeId' => $typeId,
            'day' => $day,
            // Pestañas de tipo con cuántas actividades próximas tiene cada uno
            'types' => ActivityType::orderBy('name')->get()
                ->map(fn ($type) => [$type, $upcoming->where('activity_type_id', $type->id)->count()]),
            'eventDays' => $this->eventDays(),
            'countdown' => $this->countdown(),
            'stats' => [
                'actividades' => $all->count(),
                'ponentes' => Speaker::has('activities')->count(),
                'horas' => rtrim(rtrim(number_format($all->sum(fn (Activity $a) => $a->creditedHours()), 1), '0'), '.'),
                'lugares' => (int) $all->sum('capacity'),
            ],
        ]);
    }

    // Texto de la cuenta regresiva del evento
    private function countdown(): string
    {
        $start = Carbon::parse(config('aldea.event.start_date'))->startOfDay();
        $end = Carbon::parse(config('aldea.event.end_date'))->endOfDay();

        if (now()->lt($start)) {
            $days = (int) now()->startOfDay()->diffInDays($start);

            return $days === 1 ? 'Falta 1 día' : "Faltan {$days} días";
        }

        return now()->lte($end) ? '¡El evento está en curso!' : 'El evento ya concluyó';
    }

    // Días del evento para el filtro: ['2026-11-24' => 'Martes 24', ...]
    private function eventDays(): array
    {
        $days = [];
        $period = CarbonPeriod::create(
            config('aldea.event.start_date'),
            config('aldea.event.end_date')
        );

        foreach ($period as $date) {
            $days[$date->format('Y-m-d')] = ucfirst($date->locale('es')->translatedFormat('l j'));
        }

        return $days;
    }
}
