@extends('layouts.admin')

@section('title', 'Asistencia - ' . $activity->title)

@section('content')

@php
    $statusStyles = [
        'programada' => ['Programada', 'bg-upp-surface-high text-upp-primary-container'],
        'en_curso' => ['En curso', 'bg-green-100 text-green-800'],
        'finalizada' => ['Finalizada', 'bg-gray-100 text-gray-600'],
    ];
    [$statusLabel, $statusClasses] = $statusStyles[$activity->estado];

    $tasa = $counts['inscritos'] > 0 ? round($counts['asistieron'] * 100 / $counts['inscritos']) : 0;

    // Pestañas de filtro con su conteo
    $tabs = [
        'todos' => ['Todos', $counts['inscritos']],
        'asistieron' => ['Asistieron', $counts['asistieron']],
        'sin_registro' => ['Sin registro', $counts['sinRegistro']],
    ];
@endphp

    <!-- Encabezado -->
    <div class="mb-8">

        <a href="{{ route('admin.attendances.index') }}"
           class="inline-flex items-center gap-1 text-sm text-upp-primary-container hover:underline">
            ← Volver a Asistencias
        </a>

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between mt-6">
            <div>
                <p class="text-xs font-semibold tracking-widest uppercase text-upp-secondary">
                    Administración / <span class="text-upp-primary">Lista de asistencia</span>
                </p>

                <h1 class="font-heading text-3xl sm:text-4xl font-bold text-upp-primary mt-2">
                    {{ $activity->title }}
                </h1>

                <div class="flex flex-wrap items-center gap-x-3 gap-y-2 mt-3 text-sm text-gray-600">
                    <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-medium bg-upp-secondary-container/60 text-upp-primary-container">
                        {{ $activity->activityType?->name ?? 'Sin tipo' }}
                    </span>
                    <span>{{ $activity->room?->name ?? 'Sin aula' }}</span>
                    <span>·</span>
                    <span>{{ $activity->date ? ucfirst($activity->date->locale('es')->translatedFormat('l j \d\e F')) : 'Sin fecha' }}, {{ $activity->timeRange() }} h</span>
                    <span>·</span>
                    <span>Responsable: <strong class="text-gray-800">{{ $activity->responsible?->name ?? 'Sin asignar' }}</strong></span>
                </div>

                <div class="flex flex-wrap items-center gap-2 mt-3">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $statusClasses }}">
                        @if ($activity->estado === 'en_curso')
                            <span class="w-1.5 h-1.5 rounded-full bg-green-600"></span>
                        @endif
                        {{ $statusLabel }}
                    </span>
                    <span class="text-xs {{ $activity->checkin_enabled ? 'text-green-700' : 'text-gray-500' }}">
                        Pase de lista {{ $activity->checkin_enabled ? 'abierto' : 'cerrado' }}
                    </span>
                </div>
            </div>

            <a href="{{ route('admin.attendances.export', $activity) }}"
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
            <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Inscritos</p>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $counts['inscritos'] }}</p>
            <p class="text-sm text-upp-secondary mt-1">Cupo de {{ $activity->capacity ?? '—' }}</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Asistieron</p>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $counts['asistieron'] }}</p>
            <p class="text-sm text-upp-secondary mt-1">Con asistencia registrada</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Sin registro</p>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $counts['sinRegistro'] }}</p>
            <p class="text-sm text-upp-secondary mt-1">
                {{ $activity->estado === 'finalizada' ? 'No asistieron' : 'Aún sin pasar lista' }}
            </p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Tasa de asistencia</p>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $tasa }}%</p>
            <p class="text-sm text-upp-secondary mt-1">
                @if ($counts['cancelados'] > 0)
                    {{ $counts['cancelados'] }} {{ $counts['cancelados'] === 1 ? 'canceló' : 'cancelaron' }} su inscripción
                @else
                    Asistieron entre inscritos
                @endif
            </p>
        </div>

    </div>

    <!-- Lista de alumnos -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">

        <div class="px-4 lg:px-6 py-4 border-b border-upp-surface-high flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">

            <!-- Pestañas -->
            <div class="flex flex-wrap gap-2" data-resultados="pestanas">
                @foreach ($tabs as $key => [$label, $total])
                    <a href="{{ route('admin.attendances.show', ['activity' => $activity, 'filtro' => $key, 'q' => $search ?: null]) }}"
                       class="px-3 py-1.5 rounded-lg text-sm font-medium transition
                              {{ $filtro === $key ? 'bg-upp-primary-container text-white' : 'bg-upp-surface-low text-upp-primary-container hover:bg-upp-surface-high' }}">
                        {{ $label }} <span class="{{ $filtro === $key ? 'text-white/80' : 'text-gray-500' }}">({{ $total }})</span>
                    </a>
                @endforeach
            </div>

            <!-- Búsqueda -->
            <form action="{{ route('admin.attendances.show', $activity) }}" method="GET" data-filtro-vivo class="flex gap-2 w-full lg:w-auto">
                <input type="hidden" name="filtro" value="{{ $filtro }}">
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
                        <th class="px-4 lg:px-6 py-3 font-semibold">Carrera</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Inscripción</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Asistencia</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-upp-surface-high">
                    @forelse ($participants as $participant)
                        @php
                            $words = preg_split('/\s+/', trim($participant->name));
                            $initials = mb_strtoupper(mb_substr($words[0] ?? '', 0, 1) . mb_substr($words[1] ?? '', 0, 1));
                        @endphp
                        <tr class="hover:bg-upp-surface/60">

                            <td class="px-4 lg:px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="w-9 h-9 rounded-full bg-upp-surface-high text-upp-primary-container flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ $initials }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-upp-primary">{{ $participant->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $participant->email }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-gray-700">{{ $participant->matricula ?? '—' }}</td>

                            <td class="px-4 lg:px-6 py-4 text-gray-700 min-w-[10rem]">{{ $participant->carrera ?? '—' }}</td>

                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-gray-600">
                                {{ $participant->enrolled_at ? \Illuminate\Support\Carbon::parse($participant->enrolled_at)->format('d/m/Y') : '—' }}
                            </td>

                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                @if ($participant->attended)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Asistió
                                        @if ($participant->checkin_time)
                                            · {{ \Illuminate\Support\Carbon::parse($participant->checkin_time)->format('H:i') }}
                                        @endif
                                    </span>
                                    @if ($participant->checked_in_by_name)
                                        <p class="mt-1 text-xs text-gray-500">Registró: {{ $participant->checked_in_by_name }}</p>
                                    @endif
                                @elseif ($activity->estado === 'finalizada')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        No asistió
                                    </span>
                                @else
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                        Pendiente
                                    </span>
                                @endif
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 lg:px-6 py-12 text-center text-gray-500">
                                @if ($search !== '' || $filtro !== 'todos')
                                    No hay alumnos con ese filtro.
                                @else
                                    Todavía no hay alumnos inscritos en esta actividad.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        <p class="px-4 lg:px-6 py-3 border-t border-upp-surface-high text-xs text-gray-500">
            Esta lista es de consulta. Las asistencias las registra el responsable desde el pase de lista.
        </p>

    </div>

    <!-- Paginación -->
    <div class="mt-6" data-resultados="paginacion">
        {{ $participants->links() }}
    </div>

@endsection
