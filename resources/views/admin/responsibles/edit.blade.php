@extends('layouts.admin')

@section('title', 'Editar Responsable')

@section('content')

<div class="max-w-3xl mx-auto">

    <!-- Encabezado -->
    <div class="mb-8">

        <a href="{{ route('admin.responsibles.index') }}"
           class="inline-flex items-center gap-1 text-sm text-upp-primary-container hover:underline">
            ← Volver a Gestión de Responsables
        </a>

        <p class="text-xs font-semibold tracking-widest uppercase text-upp-secondary mt-6">
            Administración / <span class="text-upp-primary">Responsables</span>
        </p>

        <h1 class="font-heading text-3xl sm:text-4xl font-bold text-upp-primary mt-2">
            Editar Responsable
        </h1>

        <p class="text-gray-600 mt-2">
            Modifica los datos de <strong class="text-upp-primary">{{ $responsible->name }}</strong>.
        </p>

    </div>

    <!-- Formulario -->
    <div class="bg-white rounded-xl shadow-sm p-6 sm:p-8">

        <form action="{{ route('admin.responsibles.update', $responsible) }}" method="POST" autocomplete="off">

            @csrf
            @method('PUT')

            @include('admin.responsibles.partials.form', ['responsible' => $responsible])

            <!-- Botones -->
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-6 border-t border-upp-surface-high">

                <a href="{{ route('admin.responsibles.index') }}"
                   class="px-6 py-3 rounded-lg border border-upp-surface-high text-center text-gray-700 hover:bg-upp-surface-low transition">
                    Cancelar
                </a>

                <button type="submit"
                        class="bg-upp-primary-container hover:bg-upp-primary-dark text-white px-6 py-3 rounded-lg font-medium shadow-sm transition">
                    Guardar Cambios
                </button>

            </div>

        </form>

    </div>

    <!-- Actividades asignadas -->
    <div class="bg-white rounded-xl shadow-sm mt-6 overflow-hidden">

        <div class="px-6 py-4 border-b border-upp-surface-high flex items-center justify-between">
            <h2 class="font-heading text-lg font-bold text-upp-primary">Actividades a su cargo</h2>
            <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-upp-surface-high text-upp-primary-container">
                {{ $activities->count() }}
            </span>
        </div>

        @forelse ($activities as $activity)
            <div class="px-6 py-4 border-b border-upp-surface-high last:border-0 flex items-start justify-between gap-4">
                <div>
                    <p class="font-semibold text-upp-primary">{{ $activity->title }}</p>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ $activity->room?->name ?? 'Sin aula' }}
                        @if ($activity->date)
                            · {{ $activity->date->format('d/m/Y') }}
                        @endif
                        @if ($activity->start_time)
                            · {{ $activity->timeRange() }} h
                        @endif
                    </p>
                </div>
                <a href="{{ route('admin.activities.edit', $activity) }}"
                   class="shrink-0 text-sm text-upp-primary-container font-medium hover:underline">
                    Ver actividad
                </a>
            </div>
        @empty
            <p class="px-6 py-6 text-sm text-gray-500">
                Todavía no tiene actividades. Se asignan desde
                <a href="{{ route('admin.activities.index') }}" class="text-upp-primary-container font-medium hover:underline">la programación de actividades</a>.
            </p>
        @endforelse

    </div>

</div>

@endsection
