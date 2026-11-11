@extends('layouts.admin')

@section('title', 'Registrar Responsable')

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
            Registrar Responsable
        </h1>

        <p class="text-gray-600 mt-2">
            Crea la cuenta de la persona que supervisará actividades y pasará lista.
        </p>

    </div>

    <!-- Formulario -->
    <div class="bg-white rounded-xl shadow-sm p-6 sm:p-8">

        <form action="{{ route('admin.responsibles.store') }}" method="POST" autocomplete="off">

            @csrf

            @include('admin.responsibles.partials.form', ['responsible' => null])

            <!-- Botones -->
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-6 border-t border-upp-surface-high">

                <a href="{{ route('admin.responsibles.index') }}"
                   class="px-6 py-3 rounded-lg border border-upp-surface-high text-center text-gray-700 hover:bg-upp-surface-low transition">
                    Cancelar
                </a>

                <button type="submit"
                        class="bg-upp-primary-container hover:bg-upp-primary-dark text-white px-6 py-3 rounded-lg font-medium shadow-sm transition">
                    Registrar Responsable
                </button>

            </div>

        </form>

    </div>

    <p class="text-xs text-gray-500 mt-4">
        La cuenta recibe el rol <strong>responsable</strong>. Después podrás asignarla a talleres y conferencias desde la programación de actividades.
    </p>

</div>

@endsection
