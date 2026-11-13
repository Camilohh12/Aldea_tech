@extends('layouts.admin')

@php
    $sinGrupo = $grupo->clave === \App\Support\Grupo::SIN_GRUPO;
    $titulo = $sinGrupo ? 'Sin grupo' : $grupo->clave;
@endphp

@section('title', 'Asistencia - ' . $titulo)

@section('content')

@php
    $horasTexto = fn (float $h) => rtrim(rtrim(number_format($h, 1), '0'), '.');
    $participacion = $grupo->alumnos > 0 ? round($grupo->asistieron * 100 / $grupo->alumnos) : 0;

    // Pestañas por estado de las horas (las mismas que ve el tutor)
    $tabs = ['todos' => 'Todos'] + \App\Support\ProgresoTutorado::ESTADOS;
    $estadoStyles = [
        'acreditado' => 'bg-green-100 text-green-800',
        'en_progreso' => 'bg-upp-surface-high text-upp-primary-container',
        'pendiente' => 'bg-gray-100 text-gray-600',
    ];
@endphp

    <!-- Encabezado -->
    <div class="mb-8">

        <a href="{{ route('admin.attendances.groups') }}"
           class="inline-flex items-center gap-1 text-sm text-upp-primary-container hover:underline">
            ← Volver a Asistencias por grupo
        </a>

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between mt-6">
            <div>
                <p class="text-xs font-semibold tracking-widest uppercase text-upp-secondary">
                    Administración / Asistencias / <span class="text-upp-primary">Grupo</span>
                </p>

                <h1 class="font-heading text-3xl sm:text-4xl font-bold text-upp-primary mt-2 {{ $sinGrupo ? '' : 'font-mono' }}">
                    {{ $titulo }}
                </h1>

                <div class="flex flex-wrap items-center gap-x-3 gap-y-2 mt-3 text-sm text-gray-600">
                    @if ($sinGrupo)
                        <span>Alumnos que aún no capturan su grupo en «Mi perfil», o irregulares cuyo tutor todavía no tiene grupo.</span>
                    @else
                        <span>{{ $grupo->carrera ?? 'Carrera sin prefijo' }}</span>
                        @if ($grupo->numero)
                            <span>·</span>
                            <span>Grupo {{ $grupo->numero }} · {{ $grupo->cuatrimestre }}° cuatrimestre</span>
                        @endif
                    @endif
                    @if ($grupo->tutores->isNotEmpty())
                        <span>·</span>
                        <span>{{ $grupo->tutores->count() === 1 ? 'Tutor' : 'Tutores' }}: <strong class="text-gray-800">{{ $grupo->tutores->implode(', ') }}</strong></span>
                    @endif
                </div>
            </div>

            <a href="{{ route('admin.attendances.group.export', $grupo->clave) }}"
               class="inline-flex items-center justify-center gap-2 shrink-0 whitespace-nowrap bg-upp-primary-container hover:bg-upp-primary-dark text-white px-5 py-3 rounded-lg font-medium shadow-sm transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Descargar lista (CSV)
            </a>
        </div>

    </div>

    <!-- Tarjetas de resumen -->
    <div class="grid gap-4 grid-cols-2 xl:grid-cols-4 mb-8">

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Alumnos</p>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $grupo->alumnos }}</p>
            <p class="text-sm text-upp-secondary mt-1">
                @if ($grupo->porTutor > 0)
                    {{ $grupo->porTutor }} {{ $grupo->porTutor === 1 ? 'irregular' : 'irregulares' }} por su tutor
                @else
                    En este grupo
                @endif
            </p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Participación</p>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $participacion }}%</p>
            <p class="text-sm text-upp-secondary mt-1">{{ $grupo->asistieron }} asistieron al menos a una</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Asistencias</p>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $grupo->asistencias }}</p>
            <p class="text-sm text-upp-secondary mt-1">De {{ $grupo->inscripciones }} {{ $grupo->inscripciones === 1 ? 'inscripción' : 'inscripciones' }}</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Liberaron horas</p>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $grupo->acreditados }}</p>
            <p class="text-sm text-upp-secondary mt-1">Con {{ $horasTexto($meta) }} h o más</p>
        </div>

    </div>

    <!-- Lista de alumnos -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">

        <div class="px-4 lg:px-6 py-4 border-b border-upp-surface-high flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">

            <!-- Pestañas -->
            <div class="flex flex-wrap gap-2" data-resultados="pestanas">
                @foreach ($tabs as $key => $label)
                    @php $activa = ($estado ?? 'todos') === $key; @endphp
                    <a href="{{ route('admin.attendances.group', ['grupo' => $grupo->clave, 'estado' => $key === 'todos' ? null : $key, 'q' => $search ?: null]) }}"
                       class="px-3 py-1.5 rounded-lg text-sm font-medium transition
                              {{ $activa ? 'bg-upp-primary-container text-white' : 'bg-upp-surface-low text-upp-primary-container hover:bg-upp-surface-high' }}">
                        {{ $label }} <span class="{{ $activa ? 'text-white/80' : 'text-gray-500' }}">({{ $conteos[$key] }})</span>
                    </a>
                @endforeach
            </div>

            <!-- Búsqueda -->
            <form action="{{ route('admin.attendances.group', $grupo->clave) }}" method="GET" data-filtro-vivo class="flex gap-2 w-full lg:w-auto">
                @if ($estado)
                    <input type="hidden" name="estado" value="{{ $estado }}">
                @endif
                <div class="relative flex-1 lg:w-72">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" />
                    </svg>
                    <input type="search" name="q" value="{{ $search }}"
                           placeholder="Buscar alumno o matrícula..."
                           class="w-full rounded-lg border border-gray-300 bg-white pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-upp-primary-container">
                </div>
                <button type="submit"
                        class="px-4 py-2 rounded-lg bg-upp-surface-high text-upp-primary-container text-sm font-medium hover:bg-upp-secondary-container transition">
                    Buscar
                </button>
            </form>
        </div>

        <div class="overflow-x-auto" data-resultados="tabla">
            <table class="w-full text-left text-sm">

                <thead class="bg-upp-surface-low text-upp-secondary text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Alumno</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Matrícula</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Tutor</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Asistencias</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Horas</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-upp-surface-high">
                    @forelse ($alumnos as $fila)
                        @php
                            $alumno = $fila->alumno;
                            $progreso = $fila->progreso;
                            $words = preg_split('/\s+/', trim($alumno->name));
                            $initials = mb_strtoupper(mb_substr($words[0] ?? '', 0, 1) . mb_substr($words[1] ?? '', 0, 1));
                        @endphp
                        <tr class="hover:bg-upp-surface/60 align-top">

                            <td class="px-4 lg:px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="w-9 h-9 rounded-full bg-upp-surface-high text-upp-primary-container flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ $initials }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-upp-primary">{{ $alumno->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $alumno->email }}</p>
                                        @if ($fila->porTutor)
                                            <span class="inline-flex mt-1 px-2 py-0.5 rounded-md text-xs font-medium bg-amber-50 text-amber-700">
                                                {{ \App\Support\Grupo::esCodigo($alumno->grupo) || ! $alumno->grupo ? $alumno->cuatrimestre : $alumno->grupo }} · grupo de su tutor
                                            </span>
                                        @elseif ($sinGrupo)
                                            <span class="inline-flex mt-1 px-2 py-0.5 rounded-md text-xs font-medium bg-amber-50 text-amber-700">
                                                {{ \App\Support\Grupo::usaElDelTutor($alumno->grupo, $alumno->cuatrimestre) ? ($alumno->grupo ?: $alumno->cuatrimestre).' · su tutor no tiene grupo' : 'Falta capturar su grupo' }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-gray-700">{{ $alumno->matricula ?? '—' }}</td>

                            <td class="px-4 lg:px-6 py-4 text-gray-700 min-w-[9rem]">{{ $alumno->tutor?->name ?? 'Sin tutor' }}</td>

                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <p class="font-semibold text-gray-800">{{ $progreso->asistencias }} / {{ $progreso->inscripcionesActivas }}</p>
                                <p class="text-xs text-gray-500 mt-1">Asistió / inscrito</p>
                            </td>

                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <p class="font-semibold text-gray-800">
                                    {{ $horasTexto($progreso->horas) }} / {{ $horasTexto($meta) }} h
                                </p>
                                <div class="w-24 h-1.5 rounded-full bg-upp-surface-high mt-2">
                                    <div class="h-1.5 rounded-full bg-upp-primary-container" style="width: {{ $progreso->porcentaje }}%"></div>
                                </div>
                                <span class="inline-flex mt-2 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $estadoStyles[$progreso->estado] }}">
                                    {{ $progreso->etiqueta() }}
                                </span>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 lg:px-6 py-12 text-center text-gray-500">
                                @if ($search !== '' || $estado)
                                    No hay alumnos con ese filtro.
                                @elseif ($sinGrupo)
                                    Todos los alumnos tienen grupo.
                                @else
                                    Este grupo no tiene alumnos.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        <p class="px-4 lg:px-6 py-3 border-t border-upp-surface-high text-xs text-gray-500">
            Esta lista es de consulta. Las horas cuentan solo actividades con asistencia registrada.
        </p>

    </div>

    <!-- Paginación -->
    <div class="mt-6" data-resultados="paginacion">
        {{ $alumnos->links() }}
    </div>

@endsection
