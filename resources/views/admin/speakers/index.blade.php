@extends('layouts.admin')

@section('title', 'Gestión de Ponentes')

@section('content')

    <!-- Encabezado -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-8">

        <div>
            <p class="text-xs font-semibold tracking-widest uppercase text-upp-secondary">
                Administración / <span class="text-upp-primary">Ponentes</span>
            </p>

            <h1 class="font-heading text-3xl sm:text-4xl font-bold text-upp-primary mt-2">
                Gestión de Ponentes
            </h1>

            <p class="text-gray-600 mt-2">
                Registra a los ponentes e instructores que impartirán las actividades.
            </p>
        </div>

        <a href="{{ route('admin.speakers.create') }}"
           class="inline-flex items-center justify-center gap-2 shrink-0 whitespace-nowrap bg-upp-primary-container hover:bg-upp-primary-dark text-white px-5 py-3 rounded-lg font-medium shadow-sm transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Nuevo Ponente
        </a>

    </div>

    <!-- Tarjetas de resumen -->
    <div class="grid gap-4 sm:grid-cols-3 mb-8">

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <div class="flex items-start justify-between">
                <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Total de ponentes</p>
                <span class="p-2 rounded-lg bg-upp-surface-high text-upp-primary-container">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </span>
            </div>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $totalSpeakers }}</p>
            <p class="text-sm text-upp-secondary mt-1">Ponentes registrados</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <div class="flex items-start justify-between">
                <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Con actividades</p>
                <span class="p-2 rounded-lg bg-upp-surface-high text-upp-primary-container">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $speakersWithActivities }}</p>
            <p class="text-sm text-upp-secondary mt-1">Ya tienen talleres o conferencias</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <div class="flex items-start justify-between">
                <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Sin asignar</p>
                <span class="p-2 rounded-lg bg-upp-surface-high text-upp-primary-container">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $totalSpeakers - $speakersWithActivities }}</p>
            <p class="text-sm text-upp-secondary mt-1">Pendientes de programar</p>
        </div>

    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">

        <div class="px-4 lg:px-6 py-4 border-b border-upp-surface-high flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <h2 class="font-heading text-lg font-bold text-upp-primary">Ponentes registrados</h2>

            <!-- Búsqueda -->
            <form action="{{ route('admin.speakers.index') }}" method="GET" data-filtro-vivo class="flex gap-2 w-full md:w-auto">
                <div class="relative flex-1 md:w-80">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" />
                    </svg>
                    <input type="search"
                           name="q"
                           value="{{ $search }}"
                           placeholder="Buscar por nombre o matrícula..."
                           class="w-full rounded-lg border border-gray-300 bg-white pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-upp-primary-container">
                </div>

                <button type="submit"
                        class="px-4 py-2 rounded-lg bg-upp-surface-high text-upp-primary-container text-sm font-medium hover:bg-upp-secondary-container transition">
                    Buscar
                </button>

                <span data-resultados="limpiar" class="contents">
                @if ($search !== '')
                    <a href="{{ route('admin.speakers.index') }}"
                       class="px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-upp-surface-low transition">
                        Limpiar
                    </a>
                @endif
                </span>
            </form>
        </div>

        <div class="overflow-x-auto" data-resultados="tabla">
            <table class="w-full text-left text-sm">

                <thead class="bg-upp-surface-low text-upp-secondary text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Ponente / Instructor</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Matrícula</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Actividades</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-upp-surface-high">
                    @forelse ($speakers as $speaker)
                        <tr class="hover:bg-upp-surface/60">

                            <td class="px-4 lg:px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="w-9 h-9 rounded-full bg-upp-surface-high text-upp-primary-container flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ $speaker->initials }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-upp-primary">{{ $speaker->name }}</p>
                                        @if ($speaker->bio)
                                            <p class="text-xs text-gray-500 truncate max-w-xs">{{ Str::limit($speaker->bio, 70) }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 lg:px-6 py-4 text-gray-700 whitespace-nowrap">
                                {{ $speaker->matricula_id ?? '—' }}
                            </td>

                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                @if ($speaker->activities_count > 0)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-upp-secondary-container/60 text-upp-primary-container">
                                        {{ $speaker->activities_count }} {{ $speaker->activities_count === 1 ? 'actividad' : 'actividades' }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                                        Sin actividades
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 lg:px-6 py-4">
                                <div class="flex justify-end items-center gap-2">

                                    <a href="{{ route('admin.speakers.edit', $speaker) }}"
                                       class="px-3 py-1.5 rounded-lg border border-upp-surface-high text-upp-primary-container font-medium hover:bg-upp-surface-low transition">
                                        Editar
                                    </a>

                                    @if ($speaker->activities_count > 0)
                                        <!-- No se puede eliminar un ponente con actividades -->
                                        <span class="px-3 py-1.5 rounded-lg text-gray-400 cursor-not-allowed"
                                              title="Tiene actividades asignadas">
                                            Eliminar
                                        </span>
                                    @else
                                        <form action="{{ route('admin.speakers.destroy', $speaker) }}"
                                              method="POST"
                                              onsubmit="return confirm('¿Eliminar al ponente ' + @js($speaker->name) + '?')">
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
                            <td colspan="4" class="px-4 lg:px-6 py-12 text-center text-gray-500">
                                @if ($search !== '')
                                    No se encontraron ponentes para «{{ $search }}».
                                @else
                                    No hay ponentes registrados.
                                    <a href="{{ route('admin.speakers.create') }}" class="text-upp-primary-container font-medium hover:underline">
                                        Registra el primero.
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
    <div class="mt-6" data-resultados="paginacion">
        {{ $speakers->links() }}
    </div>

@endsection
