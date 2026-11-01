<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityType;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Consulta de asistencias. El administrador solo consulta:
// el pase de lista lo hace el responsable de cada actividad.
class AttendanceController extends Controller
{
    // Resumen de asistencia por actividad (con búsqueda y filtros)
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $day = $request->query('dia');
        $typeId = $request->query('tipo');
        $estado = $request->query('estado');

        $activities = Activity::with(['activityType', 'room', 'responsible'])
            ->select('activities.*')
            ->selectSub($this->enrollmentsCount(), 'registered_count')
            ->selectSub($this->enrollmentsCount(attended: true), 'attended_count')
            ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->when($day, fn ($query) => $query->whereDate('date', $day))
            ->when($typeId, fn ($query) => $query->where('activity_type_id', $typeId))
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->each(fn (Activity $activity) => $activity->estado = $this->status($activity));

        // Tarjetas: se calculan con todas las actividades, sin filtros
        $all = $this->summaryOfAll();

        // El estado depende de la hora actual, por eso se filtra aquí
        $filtered = match ($estado) {
            'programada', 'en_curso', 'finalizada' => $activities->where('estado', $estado),
            'abierta' => $activities->where('checkin_enabled', true),
            default => $activities,
        };

        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginated = new LengthAwarePaginator(
            $filtered->forPage($page, 15)->values(),
            $filtered->count(),
            15,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.attendances.index', [
            'activities' => $paginated,
            'stats' => $all,
            'search' => $search,
            'day' => $day,
            'typeId' => $typeId,
            'estado' => $estado,
            'types' => ActivityType::orderBy('name')->get(),
            'eventDays' => $this->eventDays(),
        ]);
    }

    // Lista de alumnos inscritos en una actividad y su asistencia
    public function show(Request $request, Activity $activity)
    {
        $activity->load(['activityType', 'room', 'responsible', 'speakers']);
        $activity->estado = $this->status($activity);

        $search = trim((string) $request->query('q', ''));
        $filtro = $request->query('filtro', 'todos');

        $counts = [
            'inscritos' => $this->participants($activity)->count(),
            'asistieron' => $this->participants($activity)->where('enrollments.attended', true)->count(),
            'cancelados' => DB::table('enrollments')
                ->where('activity_id', $activity->id)
                ->where('status', 'cancelled')
                ->count(),
        ];
        $counts['sinRegistro'] = $counts['inscritos'] - $counts['asistieron'];

        $participants = $this->participants($activity)
            ->when($filtro === 'asistieron', fn ($query) => $query->where('enrollments.attended', true))
            ->when($filtro === 'sin_registro', fn ($query) => $query->where('enrollments.attended', false))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.matricula', 'like', "%{$search}%");
                });
            })
            ->orderBy('users.name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.attendances.show', compact(
            'activity', 'counts', 'participants', 'search', 'filtro'
        ));
    }

    // Descarga la lista de asistencia de la actividad en CSV (se abre en Excel)
    public function export(Activity $activity)
    {
        $rows = $this->participants($activity)->orderBy('users.name')->get();
        $filename = 'asistencia-' . Str::slug(Str::limit($activity->title, 50, '')) . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');

            // Marca UTF-8 para que Excel muestre bien los acentos
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Alumno', 'Correo', 'Matrícula', 'Carrera', 'Asistió', 'Hora de registro', 'Registró']);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->name,
                    $row->email,
                    $row->matricula,
                    $row->carrera,
                    $row->attended ? 'Sí' : 'No',
                    $row->checkin_time ? Carbon::parse($row->checkin_time)->format('d/m/Y H:i') : '',
                    $row->attended ? ($row->checked_in_by_name ?? '') : '',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // Inscripciones activas de la actividad con los datos del alumno
    // y de quién registró su asistencia (checked_in_by)
    private function participants(Activity $activity)
    {
        return DB::table('enrollments')
            ->join('users', 'users.id', '=', 'enrollments.user_id')
            ->leftJoin('users as staff', 'staff.id', '=', 'enrollments.checked_in_by')
            ->where('enrollments.activity_id', $activity->id)
            ->where('enrollments.status', 'registered')
            ->select(
                'users.name',
                'users.email',
                'users.matricula',
                'users.carrera',
                'enrollments.attended',
                'enrollments.checkin_time',
                'enrollments.created_at as enrolled_at',
                'staff.name as checked_in_by_name'
            );
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

    // Totales de todo el evento para las tarjetas
    private function summaryOfAll(): array
    {
        $activities = Activity::select('activities.*')
            ->selectSub($this->enrollmentsCount(), 'registered_count')
            ->selectSub($this->enrollmentsCount(attended: true), 'attended_count')
            ->get();

        $finished = $activities->filter(fn (Activity $a) => $a->hasEnded());
        $registeredInFinished = (int) $finished->sum('registered_count');

        return [
            'asistencias' => (int) $activities->sum('attended_count'),
            'inscripciones' => (int) $activities->sum('registered_count'),
            'abiertas' => $activities->where('checkin_enabled', true)->count(),
            'finalizadas' => $finished->count(),
            // Solo cuenta actividades que ya terminaron
            'tasa' => $registeredInFinished > 0
                ? round($finished->sum('attended_count') * 100 / $registeredInFinished, 1)
                : null,
        ];
    }

    // Estado de la actividad según la hora actual
    private function status(Activity $activity): string
    {
        if ($activity->hasEnded()) {
            return 'finalizada';
        }

        return $activity->isInProgress() ? 'en_curso' : 'programada';
    }

    // Días del evento para el filtro: ['2026-11-24' => 'Martes 24 de noviembre', ...]
    private function eventDays(): array
    {
        $days = [];
        $period = CarbonPeriod::create(
            config('aldea.event.start_date'),
            config('aldea.event.end_date')
        );

        foreach ($period as $date) {
            $days[$date->format('Y-m-d')] = ucfirst(
                $date->locale('es')->translatedFormat('l j \d\e F')
            );
        }

        return $days;
    }
}
