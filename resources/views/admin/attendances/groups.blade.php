@extends('layouts.admin')

@section('title', 'Asistencias por grupo')

@section('content')

@php
    $sinGrupoClave = \App\Support\Grupo::SIN_GRUPO;

    $hasFilters = $search !== '' || $carrera || $cuatrimestre;
    $field = 'rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-upp-primary-container';
    $carreraCorta = fn (?string $c) => $c ? \Illuminate\Support\Str::replaceFirst('Ingeniería en ', '', $c) : null;
    $metaTexto = rtrim(rtrim(number_format($meta, 1), '0'), '.');
@endphp

    <!-- Encabezado -->
    <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-semibold tracking-widest uppercase text-upp-secondary">
                Administración / Asistencias / <span class="text-upp-primary">Por grupo</span>
            </p>

            <h1 class="font-heading text-3xl sm:text-4xl font-bold text-upp-primary mt-2">
                Registro de Asistencias
            </h1>

            <p class="text-gray-600 mt-2">
                Participación de cada grupo. Los alumnos irregulares cuentan en el grupo de su tutor.
            </p>
        </div>

        <a href="{{ route('admin.attendances.groups.export') }}"
           class="inline-flex items-center justify-center gap-2 shrink-0 whitespace-nowrap bg-upp-primary-container hover:bg-upp-primary-dark text-white px-5 py-3 rounded-lg font-medium shadow-sm transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Descargar resumen (CSV)
        </a>
    </div>

    @include('admin.attendances._vistas', ['activa' => 'grupos'])

    <!-- Tarjetas de resumen -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-8">

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Grupos</p>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ number_format($stats['grupos']) }}</p>
            <p class="text-sm text-upp-secondary mt-1">Con al menos un alumno registrado</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Alumnos con grupo</p>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ number_format($stats['alumnos'] - $stats['sinGrupo']) }}</p>
            <p class="text-sm text-upp-secondary mt-1">
                De {{ number_format($stats['alumnos']) }} registrados
                @if ($stats['porTutor'] > 0)
                    · {{ $stats['porTutor'] }} por su tutor
                @endif
            </p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 {{ $stats['sinGrupo'] > 0 ? 'border-amber-500' : 'border-upp-primary-container' }} p-5">
            <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Sin grupo</p>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ number_format($stats['sinGrupo']) }}</p>
            <p class="text-sm text-upp-secondary mt-1">
                @if ($stats['sinGrupo'] > 0)
                    <a href="{{ route('admin.attendances.group', $sinGrupoClave) }}" class="font-medium text-upp-primary-container hover:underline">Ver quiénes faltan</a>
                    · lo capturan en «Mi perfil»
                @else
                    Todos los alumnos tienen grupo
                @endif
            </p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Liberaron horas</p>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ number_format($stats['acreditados']) }}</p>
            <p class="text-sm text-upp-secondary mt-1">Alumnos con {{ $metaTexto }} h o más</p>
        </div>

    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">

        <div class="px-4 lg:px-6 py-4 border-b border-upp-surface-high">
            <h2 class="font-heading text-lg font-bold text-upp-primary mb-4">Asistencia por grupo</h2>

            <!-- Búsqueda y filtros -->
            <form action="{{ route('admin.attendances.groups') }}" method="GET" data-filtro-vivo class="flex flex-col gap-2 lg:flex-row">
                <div class="relative flex-1">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" />
                    </svg>
                    <input type="search" name="q" value="{{ $search }}"
                           placeholder="Buscar grupo, p. ej. TIID_01..."
                           class="w-full rounded-lg border border-gray-300 bg-white pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-upp-primary-container">
                </div>

                <select name="carrera" class="{{ $field }}">
                    <option value="">Todas las carreras</option>
                    @foreach (config('aldea.carreras') as $c)
                        <option value="{{ $c }}" @selected($carrera === $c)>{{ $c }}</option>
                    @endforeach
                </select>

                <select name="cuatrimestre" class="{{ $field }}">
                    <option value="">Todos los cuatrimestres</option>
                    @foreach (range(1, 10) as $c)
                        <option value="{{ $c }}" @selected($cuatrimestre === (string) $c)>{{ $c }}° cuatrimestre</option>
                    @endforeach
                </select>

                <div class="flex gap-2">
                    <button type="submit"
                            class="flex-1 lg:flex-none px-4 py-2 rounded-lg bg-upp-surface-high text-upp-primary-container text-sm font-medium hover:bg-upp-secondary-container transition">
                        Filtrar
                    </button>

                    <span data-resultados="limpiar" class="contents">
                    @if ($hasFilters)
                        <a href="{{ route('admin.attendances.groups') }}"
                           class="px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-upp-surface-low transition">
                            Limpiar
                        </a>
                    @endif
                    </span>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto" data-resultados="tabla">
            <table class="w-full text-left text-sm">

                <thead class="bg-upp-surface-low text-upp-secondary text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Grupo</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Alumnos</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Participación</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Asistencias</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Horas</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold text-right">Acción</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-upp-surface-high">
                    @forelse ($grupos as $grupo)
                        @php
                            $sinGrupo = $grupo->clave === $sinGrupoClave;
                            $percent = $grupo->alumnos > 0 ? round($grupo->asistieron * 100 / $grupo->alumnos) : 0;
                        @endphp
                        <tr class="hover:bg-upp-surface/60 align-top {{ $sinGrupo ? 'bg-amber-50/40' : '' }}">

                            <td class="px-4 lg:px-6 py-4 min-w-[13rem]">
                                @if ($sinGrupo)
                                    <p class="font-semibold text-amber-800">Sin grupo</p>
                                    <p class="text-xs text-gray-500 mt-1">Aún no capturan su grupo</p>
                                @else
                                    <p class="font-mono font-semibold text-upp-primary">{{ $grupo->clave }}</p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ $carreraCorta($grupo->carrera) ?? 'Carrera sin prefijo' }}
                                        @if ($grupo->numero)
                                            · Grupo {{ $grupo->numero }} · {{ $grupo->cuatrimestre }}°
                                        @endif
                                    </p>
                                @endif
                            </td>

                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <p class="font-semibold text-gray-800">{{ $grupo->alumnos }}</p>
                                @if ($grupo->porTutor > 0)
                                    <p class="text-xs text-gray-500 mt-1">{{ $grupo->porTutor }} {{ $grupo->porTutor === 1 ? 'irregular' : 'irregulares' }}</p>
                                @endif
                            </td>

                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <p class="font-semibold text-gray-800">
                                    {{ $grupo->asistieron }} / {{ $grupo->alumnos }}
                                    <span class="font-normal text-gray-500">· {{ $percent }}%</span>
                                </p>
                                <div class="w-24 h-1.5 rounded-full bg-upp-surface-high mt-2">
                                    <div class="h-1.5 rounded-full bg-upp-primary-container" style="width: {{ $percent }}%"></div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Asistieron al menos a una</p>
                            </td>

                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <p class="font-semibold text-gray-800">{{ $grupo->asistencias }}</p>
                                <p class="text-xs text-gray-500 mt-1">De {{ $grupo->inscripciones }} {{ $grupo->inscripciones === 1 ? 'inscripción' : 'inscripciones' }}</p>
                            </td>

                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <p class="font-semibold text-gray-800">{{ rtrim(rtrim(number_format($grupo->horas, 1), '0'), '.') }} h</p>
                                <p class="text-xs text-gray-500 mt-1">{{ $grupo->acreditados }} {{ $grupo->acreditados === 1 ? 'liberó' : 'liberaron' }} {{ $metaTexto }} h</p>
                            </td>

                            <td class="px-4 lg:px-6 py-4 text-right">
                                <a href="{{ route('admin.attendances.group', $grupo->clave) }}"
                                   class="px-3 py-1.5 rounded-lg border border-upp-surface-high text-upp-primary-container font-medium hover:bg-upp-surface-low transition whitespace-nowrap">
                                    Ver alumnos
                                </a>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 lg:px-6 py-12 text-center text-gray-500">
                                @if ($hasFilters)
                                    No hay grupos con esos filtros.
                                @else
                                    Aún no hay alumnos registrados.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        <p class="px-4 lg:px-6 py-3 border-t border-upp-surface-high text-xs text-gray-500">
            El código del grupo es carrera_cuatrimestre_grupo, como en la lista de la coordinación (p. ej. TIID_01_02 = 1er cuatrimestre, grupo 2). El alumno lo elige de la lista al registrarse o en «Mi perfil».
        </p>

    </div>

    <!-- Paginación -->
    <div class="mt-6" data-resultados="paginacion">
        {{ $grupos->links() }}
    </div>

@endsection
