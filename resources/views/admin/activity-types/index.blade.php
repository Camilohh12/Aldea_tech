@extends('layouts.admin')

@section('title', 'Tipos de Actividad')

@section('content')

    <!-- Encabezado -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-8">

        <div>
            <p class="text-xs font-semibold tracking-widest uppercase text-upp-secondary">
                Administración / <span class="text-upp-primary">Programación</span>
            </p>

            <h1 class="font-heading text-3xl sm:text-4xl font-bold text-upp-primary mt-2">
                Tipos de Actividad
            </h1>

            <p class="text-gray-600 mt-2">
                Administra las categorías con las que se clasifican las actividades del evento.
            </p>
        </div>

        <a href="{{ route('admin.activity-types.create') }}"
           class="inline-flex items-center justify-center gap-2 shrink-0 whitespace-nowrap bg-upp-primary-container hover:bg-upp-primary-dark text-white px-5 py-3 rounded-lg font-medium shadow-sm transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Nuevo Tipo
        </a>

    </div>

    <!-- Tarjetas de resumen -->
    <div class="grid gap-4 sm:grid-cols-3 mb-8">

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Total de tipos</p>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $totalTypes }}</p>
            <p class="text-sm text-upp-secondary mt-1">Categorías registradas</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Tipos en uso</p>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $typesInUse }}</p>
            <p class="text-sm text-upp-secondary mt-1">Con actividades programadas</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Sin actividades</p>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $totalTypes - $typesInUse }}</p>
            <p class="text-sm text-upp-secondary mt-1">Disponibles para usarse</p>
        </div>

    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">

        <div class="px-4 lg:px-6 py-4 border-b border-upp-surface-high">
            <h2 class="font-heading text-lg font-bold text-upp-primary">Tipos registrados</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">

                <thead class="bg-upp-surface-low text-upp-secondary text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Tipo</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Descripción</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Actividades</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-upp-surface-high">
                    @forelse ($types as $type)
                        <tr class="hover:bg-upp-surface/60">

                            <td class="px-4 lg:px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="w-9 h-9 rounded-full bg-upp-surface-high text-upp-primary-container flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ mb_strtoupper(mb_substr($type->name, 0, 2)) }}
                                    </span>
                                    <span class="font-semibold text-upp-primary">{{ $type->name }}</span>
                                </div>
                            </td>

                            <td class="px-4 lg:px-6 py-4 text-gray-700">
                                {{ $type->description ?: '—' }}
                            </td>

                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                @if ($type->activities_count > 0)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-upp-secondary-container/60 text-upp-primary-container">
                                        {{ $type->activities_count }} {{ $type->activities_count === 1 ? 'actividad' : 'actividades' }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                                        Sin actividades
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 lg:px-6 py-4">
                                <div class="flex justify-end items-center gap-2">

                                    <a href="{{ route('admin.activity-types.edit', $type) }}"
                                       class="px-3 py-1.5 rounded-lg border border-upp-surface-high text-upp-primary-container font-medium hover:bg-upp-surface-low transition">
                                        Editar
                                    </a>

                                    @if ($type->activities_count > 0)
                                        <!-- No se puede eliminar un tipo con actividades -->
                                        <span class="px-3 py-1.5 rounded-lg text-gray-400 cursor-not-allowed"
                                              title="Tiene actividades asignadas">
                                            Eliminar
                                        </span>
                                    @else
                                        <form action="{{ route('admin.activity-types.destroy', $type) }}"
                                              method="POST"
                                              onsubmit="return confirm('¿Eliminar el tipo ' + @js($type->name) + '?')">
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
                                No hay tipos de actividad registrados.
                                <a href="{{ route('admin.activity-types.create') }}" class="text-upp-primary-container font-medium hover:underline">
                                    Registra el primero.
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
        {{ $types->links() }}
    </div>

@endsection
