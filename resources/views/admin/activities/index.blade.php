@extends('layouts.admin')

@section('title', 'Programación de Actividades')

@section('content')

    <!-- Encabezado -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-8">

        <div>
            <p class="text-xs font-semibold tracking-widest uppercase text-upp-secondary">
                Administración / <span class="text-upp-primary">Programación</span>
            </p>

            <h1 class="font-heading text-3xl sm:text-4xl font-bold text-upp-primary mt-2">
                Programación de Actividades
            </h1>

            <p class="text-gray-600 mt-2">
                Talleres, cursos, conferencias y foros del 24 al 26 de noviembre.
            </p>
        </div>

        <a href="{{ route('admin.activities.create') }}"
           class="inline-flex items-center justify-center gap-2 shrink-0 whitespace-nowrap bg-upp-primary-container hover:bg-upp-primary text-white px-5 py-3 rounded-lg font-medium shadow-sm transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Nueva Actividad
        </a>

    </div>

    <!-- Tarjetas de resumen -->
    <div class="grid gap-4 sm:grid-cols-3 mb-8">

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <div class="flex items-start justify-between">
                <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Actividades programadas</p>
                <span class="p-2 rounded-lg bg-upp-surface-high text-upp-primary-container">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </span>
            </div>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $totalActivities }}</p>
            <p class="text-sm text-upp-secondary mt-1">En los 3 días del evento</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <div class="flex items-start justify-between">
                <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Lugares ofertados</p>
                <span class="p-2 rounded-lg bg-upp-surface-high text-upp-primary-container">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </span>
            </div>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ number_format($totalSeats) }}</p>
            <p class="text-sm text-upp-secondary mt-1">Suma del cupo de todas las actividades</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <div class="flex items-start justify-between">
                <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Ponentes participando</p>
                <span class="p-2 rounded-lg bg-upp-surface-high text-upp-primary-container">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                    </svg>
                </span>
            </div>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $speakersInvolved }}</p>
            <p class="text-sm text-upp-secondary mt-1">Con al menos una actividad</p>
        </div>

    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">

        <div class="px-4 lg:px-6 py-4 border-b border-upp-surface-high">
            <h2 class="font-heading text-lg font-bold text-upp-primary mb-4">Cursos, talleres y conferencias</h2>

            <!-- Búsqueda y filtros -->
            <form action="{{ route('admin.activities.index') }}" method="GET" class="flex flex-col gap-2 md:flex-row">
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

                <div class="flex gap-2">
                    <button type="submit"
                            class="flex-1 md:flex-none px-4 py-2 rounded-lg bg-upp-surface-high text-upp-primary-container text-sm font-medium hover:bg-upp-secondary-container transition">
                        Filtrar
                    </button>

                    @if ($search !== '' || $typeId || $day)
                        <a href="{{ route('admin.activities.index') }}"
                           class="px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-upp-surface-low transition">
                            Limpiar
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">

                <thead class="bg-upp-surface-low text-upp-secondary text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Actividad</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Lugar / Fecha</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Cupo</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Ponentes</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Responsable</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-upp-surface-high">
                    @forelse ($activities as $activity)
                        @php
                            $enrolled = (int) $activity->enrolled_count;
                            $percent = $activity->capacity ? round($enrolled * 100 / $activity->capacity) : 0;
                        @endphp
                        <tr class="hover:bg-upp-surface/60 align-top">

                            <td class="px-4 lg:px-6 py-4 min-w-[14rem]">
                                <p class="font-semibold text-upp-primary">{{ $activity->title }}</p>
                                <div class="flex flex-wrap items-center gap-2 mt-1">
                                    <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-medium bg-upp-secondary-container/60 text-upp-primary-container">
                                        {{ $activity->activityType?->name ?? 'Sin tipo' }}
                                    </span>
                                    @if ($activity->duration_hours)
                                        <span class="text-xs text-gray-500">{{ (float) $activity->duration_hours }} h acreditables</span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <p class="text-gray-800">{{ $activity->room?->name ?? 'Sin aula' }}</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ $activity->date ? ucfirst($activity->date->locale('es')->translatedFormat('D j M')) : 'Sin fecha' }}
                                    @if ($activity->start_time)
                                        · {{ $activity->time_range }}
                                    @endif
                                </p>
                            </td>

                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <p class="font-semibold text-gray-800">{{ $enrolled }} / {{ $activity->capacity ?? '—' }}</p>
                                <div class="w-20 h-1.5 rounded-full bg-upp-surface-high mt-2">
                                    <div class="h-1.5 rounded-full bg-upp-primary-container" style="width: {{ min($percent, 100) }}%"></div>
                                </div>
                            </td>

                            <td class="px-4 lg:px-6 py-4 min-w-[10rem]">
                                @forelse ($activity->speakers as $speaker)
                                    <p class="text-gray-800">{{ $speaker->name }}</p>
                                @empty
                                    <span class="text-gray-400">Sin ponentes</span>
                                @endforelse
                            </td>

                            <td class="px-4 lg:px-6 py-4 min-w-[9rem]">
                                {{ $activity->responsible?->name ?? 'Sin asignar' }}
                            </td>

                            <td class="px-4 lg:px-6 py-4">
                                <div class="flex justify-end items-center gap-2">

                                    <a href="{{ route('admin.activities.edit', $activity) }}"
                                       class="px-3 py-1.5 rounded-lg border border-upp-surface-high text-upp-primary-container font-medium hover:bg-upp-surface-low transition">
                                        Editar
                                    </a>

                                    @if ($enrolled > 0)
                                        <!-- No se puede eliminar una actividad con alumnos inscritos -->
                                        <span class="px-3 py-1.5 rounded-lg text-gray-400 cursor-not-allowed"
                                              title="Tiene alumnos inscritos">
                                            Eliminar
                                        </span>
                                    @else
                                        <form action="{{ route('admin.activities.destroy', $activity) }}"
                                              method="POST"
                                              onsubmit="return confirm('¿Eliminar la actividad ' + @js($activity->title) + '?')">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    class="px-3 py-1.5 rounded-lg text-red-600 font-medium hover:bg-red-50 transition">
                                                Eliminar
                                            </button>
                                        </form>
                                    @endif

                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 lg:px-6 py-12 text-center text-gray-500">
                                @if ($search !== '' || $typeId || $day)
                                    No hay actividades con esos filtros.
                                @else
                                    Aún no hay actividades programadas.
                                    <a href="{{ route('admin.activities.create') }}" class="text-upp-primary-container font-medium hover:underline">
                                        Registra la primera.
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

    </div>

    <!-- Paginación -->
    <div class="mt-6">
        {{ $activities->links() }}
    </div>

@endsection
