@extends('layouts.admin')

@section('title', 'Gestión de Aulas')

@section('content')

    <!-- Encabezado -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-8">

        <div>
            <p class="text-xs font-semibold tracking-widest uppercase text-upp-secondary">
                Administración / <span class="text-upp-primary">Infraestructura</span>
            </p>

            <h1 class="font-heading text-3xl sm:text-4xl font-bold text-upp-primary mt-2">
                Gestión de Aulas
            </h1>

            <p class="text-gray-600 mt-2">
                Administra los espacios disponibles para talleres y conferencias.
            </p>
        </div>

        <a href="{{ route('admin.rooms.create') }}"
           class="inline-flex items-center justify-center gap-2 shrink-0 whitespace-nowrap bg-upp-primary-container hover:bg-upp-primary-dark text-white px-5 py-3 rounded-lg font-medium shadow-sm transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Nueva Aula
        </a>

    </div>

    <!-- Tarjetas de resumen -->
    <div class="grid gap-4 sm:grid-cols-3 mb-8">

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <div class="flex items-start justify-between">
                <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Total de aulas</p>
                <span class="p-2 rounded-lg bg-upp-surface-high text-upp-primary-container">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </span>
            </div>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $totalRooms }}</p>
            <p class="text-sm text-upp-secondary mt-1">Espacios registrados</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <div class="flex items-start justify-between">
                <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Capacidad total</p>
                <span class="p-2 rounded-lg bg-upp-surface-high text-upp-primary-container">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </span>
            </div>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ number_format($totalCapacity) }}</p>
            <p class="text-sm text-upp-secondary mt-1">Lugares disponibles en el campus</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <div class="flex items-start justify-between">
                <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Aulas en uso</p>
                <span class="p-2 rounded-lg bg-upp-surface-high text-upp-primary-container">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </span>
            </div>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $roomsInUse }}</p>
            <p class="text-sm text-upp-secondary mt-1">Con actividades programadas</p>
        </div>

    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">

        <div class="px-4 lg:px-6 py-4 border-b border-upp-surface-high">
            <h2 class="font-heading text-lg font-bold text-upp-primary">Aulas registradas</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">

                <thead class="bg-upp-surface-low text-upp-secondary text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Aula</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Capacidad</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Actividades</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-upp-surface-high">
                    @forelse ($rooms as $room)
                        <tr class="hover:bg-upp-surface/60">

                            <td class="px-4 lg:px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="w-9 h-9 rounded-full bg-upp-surface-high text-upp-primary-container flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ mb_strtoupper(mb_substr($room->name, 0, 2)) }}
                                    </span>
                                    <span class="font-semibold text-upp-primary">{{ $room->name }}</span>
                                </div>
                            </td>

                            <td class="px-4 lg:px-6 py-4 text-gray-700 whitespace-nowrap">
                                {{ $room->capacity }} {{ $room->capacity === 1 ? 'persona' : 'personas' }}
                            </td>

                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                @if ($room->activities_count > 0)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-upp-secondary-container/60 text-upp-primary-container">
                                        {{ $room->activities_count }} {{ $room->activities_count === 1 ? 'actividad' : 'actividades' }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                                        Sin actividades
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 lg:px-6 py-4">
                                <div class="flex justify-end items-center gap-2">

                                    <a href="{{ route('admin.rooms.edit', $room) }}"
                                       class="px-3 py-1.5 rounded-lg border border-upp-surface-high text-upp-primary-container font-medium hover:bg-upp-surface-low transition">
                                        Editar
                                    </a>

                                    @if ($room->activities_count > 0)
                                        <!-- No se puede eliminar un aula con actividades -->
                                        <span class="px-3 py-1.5 rounded-lg text-gray-400 cursor-not-allowed"
                                              title="Tiene actividades asignadas">
                                            Eliminar
                                        </span>
                                    @else
                                        <form action="{{ route('admin.rooms.destroy', $room) }}"
                                              method="POST"
                                              onsubmit="return confirm('¿Eliminar el aula ' + @js($room->name) + '?')">
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
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                No hay aulas registradas.
                                <a href="{{ route('admin.rooms.create') }}" class="text-upp-primary-container font-medium hover:underline">
                                    Registra la primera.
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

    </div>

    <!-- Paginación -->
    <div class="mt-6">
        {{ $rooms->links() }}
    </div>

@endsection
