@extends('layouts.admin')

@section('title', 'Registrar Ponente')

@section('content')

<div class="max-w-3xl mx-auto">

    <!-- Encabezado -->
    <div class="mb-8">

        <a href="{{ route('admin.speakers.index') }}"
           class="inline-flex items-center gap-1 text-sm text-upp-primary-container hover:underline">
            ← Volver a Gestión de Ponentes
        </a>

        <p class="text-xs font-semibold tracking-widest uppercase text-upp-secondary mt-6">
            Administración / <span class="text-upp-primary">Ponentes</span>
        </p>

        <h1 class="font-heading text-3xl sm:text-4xl font-bold text-upp-primary mt-2">
            Registrar Nuevo Ponente
        </h1>

        <p class="text-gray-600 mt-2">
            Añade los datos del instructor para asignarlo después a talleres y conferencias.
        </p>

    </div>

    <!-- Formulario -->
    <div class="bg-white rounded-xl shadow-sm p-6 sm:p-8">

        <form action="{{ route('admin.speakers.store') }}" method="POST">

            @csrf

            @include('admin.speakers.partials.form', ['speaker' => null])

            <!-- Botones -->
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-6 border-t border-upp-surface-high">

                <a href="{{ route('admin.speakers.index') }}"
                   class="px-6 py-3 rounded-lg border border-upp-surface-high text-center text-gray-700 hover:bg-upp-surface-low transition">
                    Cancelar
                </a>

                <button type="submit"
                        class="bg-upp-primary-container hover:bg-upp-primary text-white px-6 py-3 rounded-lg font-medium shadow-sm transition">
                    Guardar Ponente
                </button>

            </div>

        </form>

    </div>

</div>

@endsection
