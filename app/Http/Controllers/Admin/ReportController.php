<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\LeeFiltros;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Certificate;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// Reportes del evento: se ven en pantalla y se descargan en CSV
class ReportController extends Controller
{
    use LeeFiltros;

    // Reportes disponibles: clave => [título, descripción]
    private const REPORTS = [
        'inscripciones' => ['Inscripciones', 'Cupo, inscritos y cancelaciones por actividad.'],
        'asistencias' => ['Asistencias', 'Asistencia registrada en cada actividad.'],
        'talleres' => ['Talleres', 'Programación completa con responsables y ponentes.'],
        'ponentes' => ['Ponentes', 'Actividades, horas impartidas y certificados por ponente.'],
        'horas' => ['Horas acumuladas', 'Horas acreditadas por alumno contra la meta.'],
        'certificados' => ['Certificados emitidos', 'Folios generados para los ponentes.'],
    ];

    public function index(Request $request)
    {
        $type = $request->has('reporte')
            ? $this->filtroOpcion($request, 'reporte', array_keys(self::REPORTS))
            : 'inscripciones';
        abort_unless($type !== null, 404);

        $filters = $this->filters($request);
        $report = $this->build($type, $filters);

        $page = LengthAwarePaginator::resolveCurrentPage();
        $rows = new LengthAwarePaginator(
            $report['rows']->forPage($page, 25)->values(),
            $report['rows']->count(),
            25,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.reports.index', [
            'reports' => self::REPORTS,
            'type' => $type,
            'columns' => $report['columns'],
            'rows' => $rows,
            'filters' => $filters,
            'summary' => $this->summary(),
            'types' => ActivityType::orderBy('name')->get(),
            'eventDays' => $this->eventDays(),
            'carreras' => User::role('alumno')->whereNotNull('carrera')->distinct()->orderBy('carrera')->pluck('carrera'),
        ]);
    }

    // Descarga el reporte completo (con los mismos filtros) en CSV
    public function export(Request $request, string $reporte)
    {
        abort_unless(isset(self::REPORTS[$reporte]), 404);

        $report = $this->build($reporte, $this->filters($request));
        $filename = 'reporte-' . $reporte . '-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');

            // Marca UTF-8 para que Excel muestre bien los acentos
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_column($report['columns'], 0));

            foreach ($report['rows'] as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // Filtros de la URL: día y tipo (actividades) y carrera (alumnos)
    private function filters(Request $request): array
    {
        return [
            'dia' => $this->filtroFecha($request, 'dia'),
            'tipo' => $this->filtroEntero($request, 'tipo'),
            'carrera' => $this->filtroTexto($request, 'carrera', 255) ?: null,
        ];
    }

    // Columnas ([etiqueta, es número]) y filas de cada reporte
    private function build(string $type, array $filters): array
    {
        return match ($type) {
            'inscripciones' => $this->enrollmentsReport($filters),
            'asistencias' => $this->attendanceReport($filters),
            'talleres' => $this->activitiesReport($filters),
            'ponentes' => $this->speakersReport($filters),
            'horas' => $this->hoursReport($filters),
            'certificados' => $this->certificatesReport($filters),
        };
    }

    private function enrollmentsReport(array $filters): array
    {
        return [
            'columns' => [['Actividad', false], ['Tipo', false], ['Fecha', false], ['Aula', false],
                ['Cupo', true], ['Inscritos', true], ['Ocupación', true], ['Cancelaciones', true]],
            'rows' => $this->activities($filters)->map(fn (Activity $a) => [
                $a->title,
                $a->activityType?->name ?? '—',
                $this->date($a),
                $a->room?->name ?? '—',
                $a->capacity ?? 0,
                (int) $a->registered_count,
                $a->capacity ? round($a->registered_count * 100 / $a->capacity) . '%' : '—',
                (int) $a->cancelled_count,
            ]),
        ];
    }

    private function attendanceReport(array $filters): array
    {
        return [
            'columns' => [['Actividad', false], ['Fecha', false], ['Estado', false],
                ['Inscritos', true], ['Asistieron', true], ['Tasa', true]],
            'rows' => $this->activities($filters)->map(fn (Activity $a) => [
                $a->title,
                $this->date($a),
                $a->hasEnded() ? 'Finalizada' : ($a->isInProgress() ? 'En curso' : 'Programada'),
                (int) $a->registered_count,
                (int) $a->attended_count,
                $a->registered_count > 0 ? round($a->attended_count * 100 / $a->registered_count) . '%' : '—',
            ]),
        ];
    }

    private function activitiesReport(array $filters): array
    {
        return [
            'columns' => [['Actividad', false], ['Tipo', false], ['Fecha', false], ['Horario', false], ['Aula', false],
                ['Responsable', false], ['Ponentes', false], ['Cupo', true], ['Horas', true]],
            'rows' => $this->activities($filters)->map(fn (Activity $a) => [
                $a->title,
                $a->activityType?->name ?? '—',
                $this->date($a),
                $a->timeRange(),
                $a->room?->name ?? '—',
                $a->responsible?->name ?? 'Sin responsable',
                $a->speakers->pluck('name')->join(', ') ?: 'Sin ponentes',
                $a->capacity ?? 0,
                $this->hours($a->creditedHours()),
            ]),
        ];
    }

    private function speakersReport(array $filters): array
    {
        $activities = $this->activities($filters);

        $certificates = Certificate::whereIn('activity_id', $activities->pluck('id'))
            ->selectRaw('speaker_id, count(*) as total')
            ->groupBy('speaker_id')
            ->pluck('total', 'speaker_id');

        // Agrupa las actividades por ponente
        $rows = $activities
            ->flatMap(fn (Activity $a) => $a->speakers->map(fn ($speaker) => [$speaker, $a]))
            ->groupBy(fn ($pair) => $pair[0]->id)
            ->map(function (Collection $pairs) use ($certificates) {
                $speaker = $pairs->first()[0];
                $acts = $pairs->pluck(1);

                return [
                    $speaker->name,
                    $speaker->matricula_id ?? '—',
                    $acts->count(),
                    $this->hours($acts->sum(fn (Activity $a) => $a->creditedHours())),
                    (int) $acts->sum('attended_count'),
                    (int) ($certificates[$speaker->id] ?? 0),
                ];
            })
            ->sortBy(fn ($row) => $row[0])
            ->values();

        return [
            'columns' => [['Ponente', false], ['Matrícula', false], ['Actividades', true],
                ['Horas impartidas', true], ['Asistentes', true], ['Certificados', true]],
            'rows' => $rows,
        ];
    }

    private function hoursReport(array $filters): array
    {
        $meta = (float) config('aldea.horas_liberacion', 20);
        $creditedByStudent = $this->creditedHoursByStudent();

        $rows = User::role('alumno')
            ->when($filters['carrera'], fn ($query, $carrera) => $query->where('carrera', $carrera))
            ->orderBy('name')
            ->get()
            ->map(function (User $student) use ($meta, $creditedByStudent) {
                [$count, $hours] = $creditedByStudent[$student->id] ?? [0, 0];

                return [
                    $student->name,
                    $student->matricula ?? '—',
                    $student->carrera ?? '—',
                    $count,
                    $this->hours($hours),
                    $this->hours($meta),
                    $hours >= $meta ? 'Acreditado' : ($hours > 0 ? 'En progreso' : 'Sin horas'),
                ];
            });

        return [
            'columns' => [['Alumno', false], ['Matrícula', false], ['Carrera', false],
                ['Asistencias', true], ['Horas acreditadas', true], ['Meta', true], ['Estado', false]],
            'rows' => $rows,
        ];
    }

    private function certificatesReport(array $filters): array
    {
        $rows = Certificate::with(['activity', 'speaker', 'issuer'])
            ->whereHas('activity', fn ($query) => $query
                ->when($filters['dia'], fn ($q, $dia) => $q->whereDate('date', $dia))
                ->when($filters['tipo'], fn ($q, $tipo) => $q->where('activity_type_id', $tipo)))
            ->orderByDesc('issued_at')
            ->get()
            ->map(fn (Certificate $c) => [
                $c->folio,
                $c->speaker->name,
                $c->activity->title,
                $this->date($c->activity),
                $c->issued_at->format('d/m/Y H:i'),
                $c->issuer?->name ?? '—',
            ]);

        return [
            'columns' => [['Folio', false], ['Ponente', false], ['Actividad', false],
                ['Fecha de la actividad', false], ['Emitido el', false], ['Emitido por', false]],
            'rows' => $rows,
        ];
    }

    // Actividades con conteos de inscripciones, filtradas por día y tipo
    private function activities(array $filters): Collection
    {
        return Activity::with(['activityType', 'room', 'responsible', 'speakers'])
            ->select('activities.*')
            ->selectSub($this->enrollmentsCount('registered'), 'registered_count')
            ->selectSub($this->enrollmentsCount('registered', attended: true), 'attended_count')
            ->selectSub($this->enrollmentsCount('cancelled'), 'cancelled_count')
            ->when($filters['dia'], fn ($query, $dia) => $query->whereDate('date', $dia))
            ->when($filters['tipo'], fn ($query, $tipo) => $query->where('activity_type_id', $tipo))
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
    }

    // Subconsulta: inscripciones por actividad según su estado
    private function enrollmentsCount(string $status, bool $attended = false)
    {
        return DB::table('enrollments')
            ->selectRaw('count(*)')
            ->whereColumn('enrollments.activity_id', 'activities.id')
            ->where('status', $status)
            ->when($attended, fn ($query) => $query->where('attended', true));
    }

    // [alumno => [asistencias, horas]] con las horas que acredita cada actividad
    private function creditedHoursByStudent(): Collection
    {
        $activities = Activity::all()->keyBy('id');

        return DB::table('enrollments')
            ->where('status', 'registered')
            ->where('attended', true)
            ->get(['user_id', 'activity_id'])
            ->groupBy('user_id')
            ->map(fn (Collection $rows) => [
                $rows->count(),
                $rows->sum(fn ($row) => $activities->get($row->activity_id)?->creditedHours() ?? 0),
            ]);
    }

    // Totales del evento (sin filtros)
    private function summary(): array
    {
        return [
            'inscripciones' => DB::table('enrollments')->where('status', 'registered')->count(),
            'asistencias' => DB::table('enrollments')->where('status', 'registered')->where('attended', true)->count(),
            'horas' => $this->hours($this->creditedHoursByStudent()->sum(fn ($pair) => $pair[1])),
            'certificados' => Certificate::count(),
        ];
    }

    private function date(Activity $activity): string
    {
        return $activity->date ? $activity->date->format('d/m/Y') : '—';
    }

    // 2.50 → "2.5", 3.00 → "3"
    private function hours(float $hours): string
    {
        return rtrim(rtrim(number_format($hours, 1, '.', ''), '0'), '.');
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
