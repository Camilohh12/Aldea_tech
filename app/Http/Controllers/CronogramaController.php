<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LeeFiltros;
use App\Models\Activity;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

// Cronograma público: todas las actividades agrupadas por día.
// Si hoy es un día del evento, las de hoy van primero y resaltadas.
class CronogramaController extends Controller
{
    use LeeFiltros;

    public function index(Request $request)
    {
        $hoy = now()->format('Y-m-d');

        $porDia = Activity::with(['activityType', 'room', 'speakers'])
            ->withRegisteredCount()
            ->whereNotNull('date')
            ->orderBy('date')
            ->orderBy('start_time')
            ->orderBy('title')
            ->get()
            ->groupBy(fn (Activity $activity) => $activity->date->format('Y-m-d'));

        // Los días del evento siempre aparecen (aunque no tengan actividades),
        // más cualquier otra fecha que sí tenga, p. ej. un taller previo
        $fechas = collect(CarbonPeriod::create(config('aldea.event.start_date'), config('aldea.event.end_date')))
            ->map(fn (\DateTimeInterface $fecha) => $fecha->format('Y-m-d'))
            ->merge($porDia->keys())
            ->unique()
            ->sort()
            ->values();

        $dias = $fechas->map(fn (string $fecha) => $this->dia($fecha, $porDia->get($fecha, collect()), $hoy));

        // ?dia=AAAA-MM-DD muestra solo ese día; sin filtro, todos con hoy al principio
        $seleccionado = $this->filtroFecha($request, 'dia');
        $seleccionado = $fechas->contains($seleccionado) ? $seleccionado : null;

        $visibles = $seleccionado
            ? $dias->where('fecha', $seleccionado)->values()
            : $dias->sortByDesc('esHoy')->values(); // orden estable: hoy primero, el resto por fecha

        return view('cronograma', [
            'dias' => $dias,
            'visibles' => $visibles,
            'seleccionado' => $seleccionado,
            'hayHoy' => $dias->contains('esHoy', true),
            'total' => $porDia->flatten(1)->count(),
        ]);
    }

    /** Datos de un día: etiquetas y sus actividades en orden de horario. */
    private function dia(string $fecha, Collection $actividades, string $hoy): object
    {
        $carbon = Carbon::parse($fecha)->locale('es');

        return (object) [
            'fecha' => $fecha,
            'esHoy' => $fecha === $hoy,
            'corto' => ucfirst($carbon->isoFormat('ddd D')),             // «Mar 24»
            'largo' => ucfirst($carbon->isoFormat('dddd D [de] MMMM')),  // «Martes 24 de noviembre»
            'actividades' => $actividades,
        ];
    }
}
