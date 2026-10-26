@extends('layouts.admin')

@section('title', 'Panel General')

@section('content')

@php
    // Estilo y texto de cada estado de actividad
    $statusStyles = [
        'programada' => ['Programada', 'bg-upp-surface-high text-upp-primary-container'],
        'en_curso' => ['En curso', 'bg-green-100 text-green-800'],
        'llena' => ['Cupo lleno', 'bg-amber-100 text-amber-800'],
        'finalizada' => ['Finalizada', 'bg-gray-100 text-gray-600'],
    ];

    // Escala común de la gráfica por jornada
    $scale = max(1, $days->max(fn ($d) => max($d['lugares'], $d['inscritos'])));
    $pct = fn ($value) => round($value * 100 / $scale, 2);

    $occupancy = $stats['lugares'] > 0 ? round($stats['inscripciones'] * 100 / $stats['lugares']) : 0;
    $shortDate = fn ($activity) => $activity->date ? ucfirst($activity->date->locale('es')->translatedFormat('D j M')) : 'Sin fecha';
    $totalAlerts = array_sum($alerts);
@endphp

    <!-- Encabezado -->
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between mb-8">

        <div>
            <p class="text-xs font-semibold tracking-widest uppercase text-upp-secondary">
                Aldea Tecnológica UPP / <span class="text-upp-primary">Panel General</span>
            </p>

            <h1 class="font-heading text-3xl sm:text-4xl font-bold text-upp-primary mt-2">
                Panel de Control General
            </h1>

            <p class="text-gray-600 mt-2">
                Resumen en tiempo real del evento · 24 al 26 de noviembre de 2026.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
            <span class="inline-flex items-center justify-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-medium text-upp-primary shadow-sm ring-1 ring-upp-surface-high">
                <span class="w-2 h-2 rounded-full bg-upp-primary-container"></span>
                {{ $eventStatus }}
            </span>

            <a href="{{ route('admin.activities.create') }}"
               class="inline-flex items-center justify-center gap-2 shrink-0 whitespace-nowrap bg-upp-primary-container hover:bg-upp-primary text-white px-5 py-3 rounded-lg font-medium shadow-sm transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Nueva Actividad
            </a>
        </div>

    </div>

    <!-- Tarjetas de resumen -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-8">

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <div class="flex items-start justify-between">
                <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Alumnos inscritos</p>
                <span class="p-2 rounded-lg bg-upp-surface-high text-upp-primary-container">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0121 18.5c-2.62 1.256-5.6 1.976-9 1.976s-6.38-.72-9-1.976a12.08 12.08 0 012.84-7.922L12 14z" />
                    </svg>
                </span>
            </div>
            <div class="flex items-baseline gap-2 mt-2">
                <p class="font-heading text-4xl font-bold text-upp-primary">{{ number_format($stats['alumnosInscritos']) }}</p>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-upp-surface-high text-upp-primary-container"
                      title="{{ $stats['inscripciones'] }} inscripciones de {{ $stats['lugares'] }} lugares">
                    {{ $occupancy }}% del cupo
                </span>
            </div>
            <p class="text-sm text-upp-secondary mt-1">De {{ number_format($stats['alumnosRegistrados']) }} alumnos registrados</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <div class="flex items-start justify-between">
                <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Actividades programadas</p>
                <span class="p-2 rounded-lg bg-upp-surface-high text-upp-primary-container">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </span>
            </div>
            <div class="flex items-baseline gap-2 mt-2">
                <p class="font-heading text-4xl font-bold text-upp-primary">{{ $stats['actividades'] }}</p>
                @if ($stats['enCurso'] > 0)
                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-md bg-green-100 text-green-800">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-600"></span>
                        {{ $stats['enCurso'] }} en curso
                    </span>
                @endif
            </div>
            <p class="text-sm text-upp-secondary mt-1">
                {{ $stats['finalizadas'] }} {{ $stats['finalizadas'] === 1 ? 'finalizada' : 'finalizadas' }} · {{ number_format($stats['lugares']) }} lugares ofertados
            </p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <div class="flex items-start justify-between">
                <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Responsables</p>
                <span class="p-2 rounded-lg bg-upp-surface-high text-upp-primary-container">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </span>
            </div>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $stats['responsables'] }}</p>
            <p class="text-sm text-upp-secondary mt-1">{{ $stats['responsablesConActividad'] }} con actividades asignadas</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <div class="flex items-start justify-between">
                <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Horas acreditadas</p>
                <span class="p-2 rounded-lg bg-upp-surface-high text-upp-primary-container">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <div class="flex items-baseline gap-2 mt-2">
                <p class="font-heading text-4xl font-bold text-upp-primary">{{ rtrim(rtrim(number_format($stats['horas'], 1), '0'), '.') }}</p>
                @if ($stats['tasaAsistencia'] !== null)
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-upp-surface-high text-upp-primary-container"
                          title="Asistencias entre inscritos de las actividades que ya terminaron">
                        {{ rtrim(rtrim(number_format($stats['tasaAsistencia'], 1), '0'), '.') }}% asistencia
                    </span>
                @endif
            </div>
            <p class="text-sm text-upp-secondary mt-1">{{ number_format($stats['asistencias']) }} asistencias registradas</p>
        </div>

    </div>

    <div class="grid gap-6 lg:grid-cols-3 mb-6">

        <!-- Afluencia por jornada -->
        <div class="bg-white rounded-xl shadow-sm p-6 lg:col-span-2">

            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between mb-6">
                <div>
                    <h2 class="font-heading text-lg font-bold text-upp-primary">Afluencia y participación por jornada</h2>
                    <p class="text-sm text-gray-500 mt-1">Cupo ofertado, alumnos inscritos y asistencias registradas en cada día.</p>
                </div>

                <!-- Leyenda -->
                <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-600 shrink-0">
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-upp-surface-high ring-1 ring-inset ring-upp-secondary-container"></span>Cupo</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-[#9D6FC7]"></span>Inscritos</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-upp-primary-container"></span>Asistencias</span>
                </div>
            </div>

            <div class="space-y-6">
                @foreach ($days as $day)
                    <div title="{{ $day['label'] }}: {{ $day['inscritos'] }} inscritos, {{ $day['asistencias'] }} asistencias, {{ $day['lugares'] }} lugares">
                        <div class="flex items-baseline justify-between gap-3 text-sm">
                            <span class="font-semibold text-upp-primary">{{ $day['label'] }}</span>
                            <span class="text-gray-500 text-xs">{{ $day['actividades'] }} {{ $day['actividades'] === 1 ? 'actividad' : 'actividades' }}</span>
                        </div>

                        <!-- Barras anidadas: cupo > inscritos > asistencias -->
                        <div class="relative h-4 mt-2">
                            @if ($day['lugares'] > 0)
                                <div class="absolute inset-y-0 left-0 rounded bg-upp-surface-high" style="width: {{ $pct($day['lugares']) }}%"></div>
                            @endif
                            @if ($day['inscritos'] > 0)
                                <div class="absolute inset-y-0 left-0 rounded bg-[#9D6FC7] border-r-2 border-white" style="width: {{ $pct($day['inscritos']) }}%"></div>
                            @endif
                            @if ($day['asistencias'] > 0)
                                <div class="absolute inset-y-0 left-0 rounded bg-upp-primary-container border-r-2 border-white" style="width: {{ $pct($day['asistencias']) }}%"></div>
                            @endif
                            @if ($day['lugares'] === 0 && $day['inscritos'] === 0)
                                <div class="absolute inset-0 rounded border border-dashed border-gray-300"></div>
                            @endif
                        </div>

                        <p class="text-xs text-gray-600 mt-2">
                            Inscritos <strong class="text-gray-800">{{ number_format($day['inscritos']) }}</strong>
                            · Asistencias <strong class="text-gray-800">{{ number_format($day['asistencias']) }}</strong>
                            · Cupo <strong class="text-gray-800">{{ number_format($day['lugares']) }}</strong>
                        </p>
                    </div>
                @endforeach
            </div>

            <p class="text-xs text-gray-400 mt-6">
                Las asistencias se registran cuando el responsable de cada actividad pasa lista.
            </p>

        </div>

        <!-- Control de aforos -->
        <div class="bg-white rounded-xl shadow-sm p-6">

            <h2 class="font-heading text-lg font-bold text-upp-primary">Control de aforos</h2>
            <p class="text-sm text-gray-500 mt-1 mb-5">Actividades con mayor demanda.</p>

            <div class="space-y-5">
                @forelse ($demand as $activity)
                    @php
                        $ratio = round($activity->registered_count * 100 / $activity->capacity);
                    @endphp
                    <div title="{{ $activity->registered_count }} de {{ $activity->capacity }} lugares ocupados">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-upp-primary truncate">{{ $activity->title }}</p>
                                <p class="text-xs text-gray-500 truncate">
                                    {{ $activity->room?->name ?? 'Sin aula' }} · {{ $shortDate($activity) }} · {{ $activity->timeRange() }}
                                </p>
                            </div>
                            @if ($ratio >= 100)
                                <span class="shrink-0 text-xs font-semibold px-2 py-0.5 rounded-md bg-amber-100 text-amber-800">Lleno</span>
                            @endif
                        </div>
                        <div class="h-2 rounded-full bg-upp-surface-high mt-2">
                            <div class="h-2 rounded-full bg-upp-primary-container" style="width: {{ min($ratio, 100) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-600 mt-1">
                            <strong class="text-gray-800">{{ $activity->registered_count }} / {{ $activity->capacity }}</strong> · {{ $ratio }}%
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Aún no hay actividades con cupo definido.</p>
                @endforelse
            </div>

        </div>

    </div>

    <!-- Pendientes de atención -->
    @if ($stats['actividades'] > 0)
        @if ($totalAlerts > 0)
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between bg-amber-50 border border-amber-200 text-amber-900 px-5 py-4 rounded-xl mb-6">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                    <p class="text-sm">
                        <strong>Pendientes de atención:</strong>
                        @php
                            $items = array_filter([
                                $alerts['sinResponsable'] ? $alerts['sinResponsable'].' sin responsable' : null,
                                $alerts['sinPonentes'] ? $alerts['sinPonentes'].' sin ponentes' : null,
                                $alerts['llenas'] ? $alerts['llenas'].' con cupo lleno' : null,
                            ]);
                        @endphp
                        {{ implode(' · ', $items) }}.
                    </p>
                </div>
                <a href="{{ route('admin.activities.index') }}" class="shrink-0 text-sm font-semibold underline">Revisar actividades</a>
            </div>
        @else
            <div class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 px-5 py-4 rounded-xl mb-6">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm">Todo en orden: todas las actividades tienen responsable y ponentes, y ninguna tiene el cupo lleno.</p>
            </div>
        @endif
    @endif

    <!-- Supervisión de sesiones -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">

        <div class="px-4 lg:px-6 py-4 border-b border-upp-surface-high flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="font-heading text-lg font-bold text-upp-primary">Supervisión de sesiones</h2>
                <p class="text-sm text-gray-500 mt-1">Actividades en curso y próximas.</p>
            </div>

            <!-- Conteo por estado -->
            <div class="flex flex-wrap gap-2">
                @foreach ($statusStyles as $key => [$label, $classes])
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $classes }}">
                        {{ $label }} <strong>{{ $statusCounts[$key] ?? 0 }}</strong>
                    </span>
                @endforeach
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">

                <thead class="bg-upp-surface-low text-upp-secondary text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Sesión</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Aula / Horario</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Responsable</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Aforo</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Estado</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold text-right">Acción</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-upp-surface-high">
                    @forelse ($upcoming as $activity)
                        @php
                            [$statusLabel, $statusClasses] = $statusStyles[$activity->estado];
                            $ratio = $activity->capacity ? round($activity->registered_count * 100 / $activity->capacity) : 0;
                        @endphp
                        <tr class="hover:bg-upp-surface/60 align-top">

                            <td class="px-4 lg:px-6 py-4 min-w-[14rem]">
                                <p class="font-semibold text-upp-primary">{{ $activity->title }}</p>
                                <span class="inline-flex mt-1 px-2 py-0.5 rounded-md text-xs font-medium bg-upp-secondary-container/60 text-upp-primary-container">
                                    {{ $activity->activityType?->name ?? 'Sin tipo' }}
                                </span>
                            </td>

                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <p class="text-gray-800">{{ $activity->room?->name ?? 'Sin aula' }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ $shortDate($activity) }} · {{ $activity->timeRange() }}</p>
                            </td>

                            <td class="px-4 lg:px-6 py-4 min-w-[9rem]">
                                @if ($activity->responsible)
                                    {{ $activity->responsible->name }}
                                @else
                                    <span class="text-red-600 font-medium">Sin responsable</span>
                                @endif
                            </td>

                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <p class="font-semibold text-gray-800">{{ $activity->registered_count }} / {{ $activity->capacity ?? '—' }}</p>
                                <div class="w-20 h-1.5 rounded-full bg-upp-surface-high mt-2">
                                    <div class="h-1.5 rounded-full bg-upp-primary-container" style="width: {{ min($ratio, 100) }}%"></div>
                                </div>
                            </td>

                            <td class="px-4 lg:px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap {{ $statusClasses }}">
                                    @if ($activity->estado === 'en_curso')
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-600"></span>
                                    @endif
                                    {{ $statusLabel }}
                                </span>
                                @if ($activity->checkin_enabled)
                                    <p class="text-xs text-green-700 mt-1 whitespace-nowrap">Pase de lista abierto</p>
                                @endif
                            </td>

                            <td class="px-4 lg:px-6 py-4 text-right">
                                <a href="{{ route('admin.activities.edit', $activity) }}"
                                   class="px-3 py-1.5 rounded-lg border border-upp-surface-high text-upp-primary-container font-medium hover:bg-upp-surface-low transition whitespace-nowrap">
                                    Ver
                                </a>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 lg:px-6 py-12 text-center text-gray-500">
                                @if ($stats['actividades'] > 0)
                                    Todas las actividades ya terminaron.
                                @else
                                    Aún no hay actividades programadas.
                                    <a href="{{ route('admin.activities.create') }}" class="text-upp-primary-container font-medium hover:underline">Programa la primera.</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        @if ($stats['actividades'] > $upcoming->count())
            <div class="px-4 lg:px-6 py-3 border-t border-upp-surface-high text-right">
                <a href="{{ route('admin.activities.index') }}" class="text-sm font-medium text-upp-primary-container hover:underline">
                    Ver toda la programación →
                </a>
            </div>
        @endif

    </div>

@endsection
