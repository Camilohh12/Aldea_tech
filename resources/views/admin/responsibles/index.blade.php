@extends('layouts.admin')

@section('title', 'Gestión de Responsables')

@section('content')

@php
    // Nombres para mostrar de los demás roles de la cuenta
    $roleLabels = [
        'super_admin' => 'Super Admin',
        'admin' => 'Administrador',
        'tutor' => 'Tutor',
        'docente' => 'Docente',
        'alumno' => 'Alumno',
        'ponente' => 'Ponente',
    ];
@endphp

    <!-- Encabezado -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-8">

        <div>
            <p class="text-xs font-semibold tracking-widest uppercase text-upp-secondary">
                Administración / <span class="text-upp-primary">Responsables</span>
            </p>

            <h1 class="font-heading text-3xl sm:text-4xl font-bold text-upp-primary mt-2">
                Gestión de Responsables
            </h1>

            <p class="text-gray-600 mt-2">
                Personas encargadas de supervisar las actividades y pasar lista.
            </p>
        </div>

        <a href="{{ route('admin.responsibles.create') }}"
           class="inline-flex items-center justify-center gap-2 shrink-0 whitespace-nowrap bg-upp-primary-container hover:bg-upp-primary text-white px-5 py-3 rounded-lg font-medium shadow-sm transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Nuevo Responsable
        </a>

    </div>

    <!-- Tarjetas de resumen -->
    <div class="grid gap-4 sm:grid-cols-3 mb-8">

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <div class="flex items-start justify-between">
                <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Responsables</p>
                <span class="p-2 rounded-lg bg-upp-surface-high text-upp-primary-container">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </span>
            </div>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $totalResponsibles }}</p>
            <p class="text-sm text-upp-secondary mt-1">Cuentas registradas</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <div class="flex items-start justify-between">
                <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Con actividades</p>
                <span class="p-2 rounded-lg bg-upp-surface-high text-upp-primary-container">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </span>
            </div>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $withActivities }}</p>
            <p class="text-sm text-upp-secondary mt-1">Ya supervisan al menos una</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <div class="flex items-start justify-between">
                <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Disponibles</p>
                <span class="p-2 rounded-lg bg-upp-surface-high text-upp-primary-container">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $totalResponsibles - $withActivities }}</p>
            <p class="text-sm text-upp-secondary mt-1">Sin actividades asignadas</p>
        </div>

    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">

        <div class="px-4 lg:px-6 py-4 border-b border-upp-surface-high flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <h2 class="font-heading text-lg font-bold text-upp-primary">Responsables registrados</h2>

            <!-- Búsqueda -->
            <form action="{{ route('admin.responsibles.index') }}" method="GET" class="flex gap-2 w-full md:w-auto">
                <div class="relative flex-1 md:w-80">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" />
                    </svg>
                    <input type="search" name="q" value="{{ $search }}"
                           placeholder="Buscar por nombre o correo..."
                           class="w-full rounded-lg border border-gray-300 bg-white pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-upp-primary-container">
                </div>

                <button type="submit"
                        class="px-4 py-2 rounded-lg bg-upp-surface-high text-upp-primary-container text-sm font-medium hover:bg-upp-secondary-container transition">
                    Buscar
                </button>

                @if ($search !== '')
                    <a href="{{ route('admin.responsibles.index') }}"
                       class="px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-upp-surface-low transition">
                        Limpiar
                    </a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">

                <thead class="bg-upp-surface-low text-upp-secondary text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Responsable</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Actividades</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Otros roles</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Alta</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-upp-surface-high">
                    @forelse ($responsibles as $responsible)
                        @php
                            $words = preg_split('/\s+/', trim($responsible->name));
                            $initials = mb_strtoupper(mb_substr($words[0] ?? '', 0, 1) . mb_substr($words[1] ?? '', 0, 1));
                            $otherRoles = $responsible->roles->pluck('name')->reject(fn ($role) => $role === 'responsable');
                            $count = (int) $responsible->activities_count;
                        @endphp
                        <tr class="hover:bg-upp-surface/60">

                            <td class="px-4 lg:px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="w-9 h-9 rounded-full bg-upp-surface-high text-upp-primary-container flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ $initials }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-upp-primary">{{ $responsible->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $responsible->email }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                @if ($count > 0)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-upp-secondary-container/60 text-upp-primary-container">
                                        {{ $count }} {{ $count === 1 ? 'actividad' : 'actividades' }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                                        Sin actividades
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 lg:px-6 py-4">
                                @forelse ($otherRoles as $role)
                                    <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-700 mr-1">
                                        {{ $roleLabels[$role] ?? ucfirst($role) }}
                                    </span>
                                @empty
                                    <span class="text-gray-400">—</span>
                                @endforelse
                            </td>

                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-gray-600">
                                {{ $responsible->created_at?->format('d/m/Y') }}
                            </td>

                            <td class="px-4 lg:px-6 py-4">
                                <div class="flex justify-end items-center gap-2">

                                    <a href="{{ route('admin.responsibles.edit', $responsible) }}"
                                       class="px-3 py-1.5 rounded-lg border border-upp-surface-high text-upp-primary-container font-medium hover:bg-upp-surface-low transition">
                                        Editar
                                    </a>

                                    @if ($count > 0)
                                        <!-- No se puede eliminar a quien tiene actividades -->
                                        <span class="px-3 py-1.5 rounded-lg text-gray-400 cursor-not-allowed whitespace-nowrap"
                                              title="Tiene actividades asignadas">
                                            {{ $otherRoles->isNotEmpty() ? 'Quitar rol' : 'Eliminar' }}
                                        </span>
                                    @else
                                        @php
                                            $confirmMessage = $otherRoles->isNotEmpty()
                                                ? "¿Quitar el rol de responsable a {$responsible->name}? Su cuenta se conserva."
                                                : "¿Eliminar la cuenta de {$responsible->name}?";
                                        @endphp
                                        <form action="{{ route('admin.responsibles.destroy', $responsible) }}"
                                              method="POST"
                                              onsubmit="return confirm(@js($confirmMessage))">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    class="px-3 py-1.5 rounded-lg text-red-600 font-medium hover:bg-red-50 transition whitespace-nowrap">
                                                {{ $otherRoles->isNotEmpty() ? 'Quitar rol' : 'Eliminar' }}
                                            </button>
                                        </form>
                                    @endif

                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 lg:px-6 py-12 text-center text-gray-500">
                                @if ($search !== '')
                                    No se encontraron responsables para «{{ $search }}».
                                @else
                                    No hay responsables registrados.
                                    <a href="{{ route('admin.responsibles.create') }}" class="text-upp-primary-container font-medium hover:underline">
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
    <div class="mt-6">
        {{ $responsibles->links() }}
    </div>

@endsection
