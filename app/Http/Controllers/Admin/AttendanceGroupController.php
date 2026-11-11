<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\LeeFiltros;
use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\User;
use App\Support\Csv;
use App\Support\Grupo;
use App\Support\ProgresoTutorado;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Asistencias por grupo (solo consulta, como la vista por taller).
 *
 * El grupo de cada alumno es el código que capturó (sftw_01_09) o, si su
 * cuatrimestre es «Irregular» u «Otro», el de su tutor (App\Support\Grupo).
 * Horas y estado con las mismas reglas que ve el tutor (ProgresoTutorado).
 */
class AttendanceGroupController extends Controller
{
    use LeeFiltros;

    // Lista de grupos con su participación
    public function index(Request $request)
    {
        $search = $this->filtroTexto($request, 'q');
        $carrera = $this->filtroOpcion($request, 'carrera', config('aldea.carreras'));
        $cuatrimestre = $this->filtroOpcion($request, 'cuatrimestre', array_map('strval', range(1, 10)));

        $alumnos = $this->alumnos();
        $grupos = $this->resumen($alumnos);
        $busqueda = $this->normalizar($search);

        $filtrados = $grupos
            ->when($busqueda !== '', fn (Collection $c) => $c->filter(
                fn (object $g) => Str::contains($this->normalizar($g->clave.' '.$g->carrera), $busqueda)
            ))
            ->when($carrera, fn (Collection $c) => $c->where('carrera', $carrera))
            ->when($cuatrimestre, fn (Collection $c) => $c->where('cuatrimestre', (int) $cuatrimestre))
            ->values();

        $sinGrupo = $grupos->firstWhere('clave', Grupo::SIN_GRUPO);

        return view('admin.attendances.groups', [
            'grupos' => $this->paginar($request, $filtrados, 20),
            'stats' => [
                'grupos' => $grupos->where('clave', '!=', Grupo::SIN_GRUPO)->count(),
                'alumnos' => $alumnos->count(),
                'sinGrupo' => $sinGrupo->alumnos ?? 0,
                'porTutor' => $alumnos->where('porTutor', true)->count(),
                'acreditados' => $grupos->sum('acreditados'),
            ],
            'search' => $search,
            'carrera' => $carrera,
            'cuatrimestre' => $cuatrimestre,
            'meta' => (float) config('aldea.horas_liberacion', 20),
        ]);
    }

    // Alumnos de un grupo y su asistencia
    public function show(Request $request, string $grupo)
    {
        $alumnos = $this->alumnosDelGrupo($grupo);

        $search = $this->filtroTexto($request, 'q');
        $estado = $this->filtroOpcion($request, 'estado', array_keys(ProgresoTutorado::ESTADOS));
        $busqueda = $this->normalizar($search);

        $filtrados = $alumnos
            ->when($busqueda !== '', fn (Collection $c) => $c->filter(fn (object $a) => Str::contains(
                $this->normalizar($a->alumno->name.' '.$a->alumno->matricula.' '.$a->alumno->email),
                $busqueda,
            )))
            ->when($estado, fn (Collection $c) => $c->filter(fn (object $a) => $a->progreso->estado === $estado))
            ->values();

        return view('admin.attendances.group', [
            'grupo' => $this->fila($grupo, $alumnos),
            'alumnos' => $this->paginar($request, $filtrados, 25),
            'conteos' => collect(ProgresoTutorado::ESTADOS)
                ->map(fn ($etiqueta, $clave) => $alumnos->filter(fn (object $a) => $a->progreso->estado === $clave)->count())
                ->put('todos', $alumnos->count()),
            'search' => $search,
            'estado' => $estado,
            'meta' => (float) config('aldea.horas_liberacion', 20),
        ]);
    }

    // Lista del grupo en CSV (se abre en Excel)
    public function export(string $grupo)
    {
        $filas = $this->alumnosDelGrupo($grupo)->map(fn (object $a) => [
            $a->grupo ?? 'Sin grupo',
            $a->alumno->name,
            $a->alumno->matricula,
            $a->alumno->email,
            $a->alumno->carrera,
            $a->alumno->cuatrimestre,
            $a->alumno->tutor?->name,
            $a->porTutor ? 'Grupo de su tutor' : ($a->grupo ? 'Lo capturó el alumno' : 'Sin capturar'),
            $a->progreso->inscripcionesActivas,
            $a->progreso->asistencias,
            $a->progreso->horas,
            $a->progreso->etiqueta(),
            $a->progreso->inscripciones
                ->filter(fn (Enrollment $e) => $e->isRegistered() && $e->attended)
                ->map(fn (Enrollment $e) => $e->activity->title)
                ->implode('; '),
        ]);

        return Csv::descargar('asistencia-grupo-'.$grupo.'.csv', [
            'Grupo', 'Alumno', 'Matrícula', 'Correo', 'Carrera', 'Cuatrimestre', 'Tutor', 'Origen del grupo',
            'Inscripciones', 'Asistencias', 'Horas acreditadas', 'Estado', 'Actividades con asistencia',
        ], $filas);
    }

    // Resumen de todos los grupos en CSV
    public function exportSummary()
    {
        $filas = $this->resumen($this->alumnos())->map(fn (object $g) => [
            $g->clave === Grupo::SIN_GRUPO ? 'Sin grupo' : $g->clave,
            $g->carrera,
            $g->cuatrimestre,
            $g->numero,
            $g->alumnos,
            $g->porTutor,
            $g->asistieron,
            $g->inscripciones,
            $g->asistencias,
            $g->horas,
            $g->acreditados,
            $g->tutores->implode('; '),
        ]);

        return Csv::descargar('asistencia-por-grupo-'.now()->format('Y-m-d').'.csv', [
            'Grupo', 'Carrera', 'Cuatrimestre', 'Número de grupo', 'Alumnos', 'Irregulares (grupo del tutor)',
            'Alumnos con asistencia', 'Inscripciones', 'Asistencias', 'Horas acreditadas', 'Liberaron horas', 'Tutores',
        ], $filas);
    }

    /**
     * Todos los alumnos con su progreso y el grupo en el que cuentan.
     *
     * @return Collection<int, object{alumno: User, progreso: ProgresoTutorado, grupo: ?string, porTutor: bool}>
     */
    private function alumnos(): Collection
    {
        $usuarios = User::whereHas('roles', fn ($query) => $query->where('name', 'alumno'))
            ->with(['enrollments.activity', 'tutor:id,name'])
            ->orderBy('name')
            ->get();

        $grupos = Grupo::resolver($usuarios);

        return $usuarios->toBase()->map(fn (User $usuario) => (object) [
            'alumno' => $usuario,
            'progreso' => new ProgresoTutorado($usuario),
            'grupo' => $grupos[$usuario->id]['grupo'],
            'porTutor' => $grupos[$usuario->id]['por_tutor'],
        ]);
    }

    /** Alumnos de un grupo; 404 si el código no tiene alumnos («sin-grupo» siempre existe). */
    private function alumnosDelGrupo(string $grupo): Collection
    {
        $alumnos = $this->alumnos()
            ->filter(fn (object $a) => ($a->grupo ?? Grupo::SIN_GRUPO) === $grupo)
            ->values();

        abort_if($alumnos->isEmpty() && $grupo !== Grupo::SIN_GRUPO, 404);

        return $alumnos;
    }

    /** Una fila por grupo, ordenadas por carrera, cuatrimestre y número; «Sin grupo» al final. */
    private function resumen(Collection $alumnos): Collection
    {
        $carreras = array_flip(config('aldea.carreras'));

        return $alumnos
            ->groupBy(fn (object $a) => $a->grupo ?? Grupo::SIN_GRUPO)
            ->map(fn (Collection $lista, string $clave) => $this->fila($clave, $lista))
            ->sortBy(fn (object $g) => sprintf(
                '%d-%02d-%02d-%02d-%s',
                $g->clave === Grupo::SIN_GRUPO ? 1 : 0,
                $carreras[$g->carrera] ?? 99,
                $g->cuatrimestre ?? 0,
                $g->numero ?? 0,
                $g->clave,
            ))
            ->values();
    }

    private function fila(string $clave, Collection $lista): object
    {
        $partes = Grupo::partes($clave);
        $estado = fn (string $e) => $lista->filter(fn (object $a) => $a->progreso->estado === $e)->count();

        return (object) [
            'clave' => $clave,
            'carrera' => $partes['carrera'] ?? null,
            'numero' => $partes['numero'] ?? null,
            'cuatrimestre' => $partes['cuatrimestre'] ?? null,
            'alumnos' => $lista->count(),
            'porTutor' => $lista->where('porTutor', true)->count(),
            'asistieron' => $lista->filter(fn (object $a) => $a->progreso->asistencias > 0)->count(),
            'inscripciones' => $lista->sum(fn (object $a) => $a->progreso->inscripcionesActivas),
            'asistencias' => $lista->sum(fn (object $a) => $a->progreso->asistencias),
            'horas' => round($lista->sum(fn (object $a) => $a->progreso->horas), 1),
            'acreditados' => $estado(ProgresoTutorado::ACREDITADO),
            'tutores' => $lista->map(fn (object $a) => $a->alumno->tutor?->name)->filter()->unique()->sort()->values(),
        ];
    }

    private function paginar(Request $request, Collection $items, int $porPagina): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items->forPage($page, $porPagina)->values(),
            $items->count(),
            $porPagina,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    /** Para buscar sin importar mayúsculas ni acentos. */
    private function normalizar(string $texto): string
    {
        return Str::lower(Str::ascii($texto));
    }
}
