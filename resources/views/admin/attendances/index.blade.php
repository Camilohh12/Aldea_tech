@extends('layouts.admin')

@section('title', 'Asistencias')

@section('content')

@php
    // Estilo y texto de cada estado de actividad
    $statusStyles = [
        'programada' => ['Programada', 'bg-upp-surface-high text-upp-primary-container'],
        'en_curso' => ['En curso', 'bg-green-100 text-green-800'],
        'finalizada' => ['Finalizada', 'bg-gray-100 text-gray-600'],
    ];
    $hasFilters = $search !== '' || $day || $typeId || $estado;
@endphp

    <!-- Encabezado -->
    <div class="mb-8">
        <p class="text-xs font-semibold tracking-widest uppercase text-upp-secondary">
            Administración / <span class="text-upp-primary">Asistencias</span>
        </p>

        <h1 class="font-heading text-3xl sm:text-4xl font-bold text-upp-primary mt-2">
            Registro de Asistencias
        </h1>

        <p class="text-gray-600 mt-2">
            Consulta quién asistió a cada actividad. El pase de lista lo hace el responsable asignado.
        </p>
    </div>

    @include('admin.attendances._vistas', ['activa' => 'talleres'])

    <!-- Tarjetas de resumen -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-8">

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <div class="flex items-start justify-between">
                <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Asistencias registradas</p>
                <span class="p-2 rounded-lg bg-upp-surface-high text-upp-primary-container">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ number_format($stats['asistencias']) }}</p>
            <p class="text-sm text-upp-secondary mt-1">De {{ number_format($stats['inscripciones']) }} inscripciones activas</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <div class="flex items-start justify-between">
                <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Tasa de asistencia</p>
                <span class="p-2 rounded-lg bg-upp-surface-high text-upp-primary-container">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </span>
            </div>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">
                {{ $stats['tasa'] !== null ? rtrim(rtrim(number_format($stats['tasa'], 1), '0'), '.') . '%' : '—' }}
            </p>
            <p class="text-sm text-upp-secondary mt-1">
                @if ($stats['tasa'] !== null)
                    En {{ $stats['finalizadas'] }} {{ $stats['finalizadas'] === 1 ? 'actividad finalizada' : 'actividades finalizadas' }}
                @else
                    Aún no termina ninguna actividad
                @endif
            </p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <div class="flex items-start justify-between">
                <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Pase de lista abierto</p>
                <span class="p-2 rounded-lg bg-upp-surface-high text-upp-primary-container">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>
                </span>
            </div>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $stats['abiertas'] }}</p>
            <p class="text-sm text-upp-secondary mt-1">Actividades recibiendo asistencias</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <div class="flex items-start justify-between">
                <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Sin registro</p>
                <span class="p-2 rounded-lg bg-upp-surface-high text-upp-primary-container">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ number_format($stats['inscripciones'] - $stats['asistencias']) }}</p>
            <p class="text-sm text-upp-secondary mt-1">Inscripciones sin asistencia todavía</p>
        </div>

    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">

        <div class="px-4 lg:px-6 py-4 border-b border-upp-surface-high">
            <h2 class="font-heading text-lg font-bold text-upp-primary mb-4">Asistencia por actividad</h2>

            <!-- Búsqueda y filtros -->
            <form action="{{ route('admin.attendances.index') }}" method="GET" data-filtro-vivo class="flex flex-col gap-2 lg:flex-row">
                <div class="relative flex-1">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" />
                    </svg>
                    <input type="search" name="q" value="{{ $search }}"
                           placeholder="Buscar por nombre de la actividad..."
                           class="w-full rounded-lg border border-gray-300 bg-white pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-upp-primary-container">
                </div>

                <select name="tipo" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-upp-primary-container">
                    <option value="">Todos los tipos</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->id }}" @selected($typeId == $type->id)>{{ $type->name }}</option>
                    @endforeach
                </select>

                <select name="dia" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-upp-primary-container">
                    <option value="">Todos los días</option>
                    @foreach ($eventDays as $value => $label)
                        <option value="{{ $value }}" @selected($day === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="estado" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-upp-primary-container">
                    <option value="">Todos los estados</option>
                    <option value="programada" @selected($estado === 'programada')>Programadas</option>
                    <option value="en_curso" @selected($estado === 'en_curso')>En curso</option>
                    <option value="finalizada" @selected($estado === 'finalizada')>Finalizadas</option>
                    <option value="abierta" @selected($estado === 'abierta')>Con pase de lista abierto</option>
                </select>

                <div class="flex gap-2">
                    <button type="submit"
                            class="flex-1 lg:flex-none px-4 py-2 rounded-lg bg-upp-surface-high text-upp-primary-container text-sm font-medium hover:bg-upp-secondary-container transition">
                        Filtrar
                    </button>

                    <span data-resultados="limpiar" class="contents">
                    @if ($hasFilters)
                        <a href="{{ route('admin.attendances.index') }}"
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
                        <th class="px-4 lg:px-6 py-3 font-semibold">Actividad</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Lugar / Fecha</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Responsable</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Asistencia</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Estado</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold text-right">Acción</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-upp-surface-high">
                    @forelse ($activities as $activity)
                        @php
                            [$statusLabel, $statusClasses] = $statusStyles[$activity->estado];
                            $registered = (int) $activity->registered_count;
                            $attended = (int) $activity->attended_count;
                            $percent = $registered > 0 ? round($attended * 100 / $registered) : 0;
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
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ $activity->date ? ucfirst($activity->date->locale('es')->translatedFormat('D j M')) : 'Sin fecha' }}
                                    · {{ $activity->timeRange() }}
                                </p>
                            </td>

                            <td class="px-4 lg:px-6 py-4 min-w-[9rem]">
                                @if ($activity->responsible)
                                    {{ $activity->responsible->name }}
                                @else
                                    <span class="text-red-600 font-medium">Sin responsable</span>
                                @endif
                            </td>

                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <p class="font-semibold text-gray-800">
                                    {{ $attended }} / {{ $registered }}
                                    <span class="font-normal text-gray-500">· {{ $percent }}%</span>
                                </p>
                                <div class="w-24 h-1.5 rounded-full bg-upp-surface-high mt-2">
                                    <div class="h-1.5 rounded-full bg-upp-primary-container" style="width: {{ $percent }}%"></div>
                                </div>
                            </td>

                            <td class="px-4 lg:px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap {{ $statusClasses }}">
                                    @if ($activity->estado === 'en_curso')
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-600"></span>
                                    @endif
                                    {{ $statusLabel }}
                                </span>
                                <p class="text-xs mt-1 whitespace-nowrap {{ $activity->checkin_enabled ? 'text-green-700' : 'text-gray-400' }}">
                                    Pase de lista {{ $activity->checkin_enabled ? 'abierto' : 'cerrado' }}
                                </p>
                            </td>

                            <td class="px-4 lg:px-6 py-4 text-right">
                                <a href="{{ route('admin.attendances.show', $activity) }}"
                                   class="px-3 py-1.5 rounded-lg border border-upp-surface-high text-upp-primary-container font-medium hover:bg-upp-surface-low transition whitespace-nowrap">
                                    Ver lista
                                </a>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 lg:px-6 py-12 text-center text-gray-500">
                                @if ($hasFilters)
                                    No hay actividades con esos filtros.
                                @else
                                    Aún no hay actividades programadas.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

    </div>

    <!-- Paginación -->
    <div class="mt-6" data-resultados="paginacion">
        {{ $activities->links() }}
    </div>

@endsection
