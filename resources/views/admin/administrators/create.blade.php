@extends('layouts.admin')

@section('title', 'Registrar Administrador')

@section('content')

<div class="max-w-3xl mx-auto">

    <!-- Encabezado -->
    <div class="mb-8">

        <a href="{{ route('admin.administrators.index') }}"
           class="inline-flex items-center gap-1 text-sm text-upp-primary-container hover:underline">
            ← Volver a Administradores
        </a>

        <p class="text-xs font-semibold tracking-widest uppercase text-upp-secondary mt-6">
            Administración / <span class="text-upp-primary">Administradores</span>
        </p>

        <h1 class="font-heading text-3xl sm:text-4xl font-bold text-upp-primary mt-2">
            Registrar Administrador
        </h1>

        <p class="text-gray-600 mt-2">
            Crea la cuenta de una persona que apoyará en la administración del evento.
        </p>

    </div>

    <!-- Formulario -->
    <div class="bg-white rounded-xl shadow-sm p-6 sm:p-8">

        <form action="{{ route('admin.administrators.store') }}" method="POST" autocomplete="off">

            @csrf

            @include('admin.administrators.partials.form', ['administrator' => null])

            <!-- Botones -->
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-6 border-t border-upp-surface-high">

                <a href="{{ route('admin.administrators.index') }}"
                   class="px-6 py-3 rounded-lg border border-upp-surface-high text-center text-gray-700 hover:bg-upp-surface-low transition">
                    Cancelar
                </a>

                <button type="submit"
                        class="bg-upp-primary-container hover:bg-upp-primary-dark text-white px-6 py-3 rounded-lg font-medium shadow-sm transition">
                    Registrar Administrador
                </button>

            </div>

        </form>

    </div>

    <p class="text-xs text-gray-500 mt-4">
        La cuenta recibe el rol <strong>admin</strong>: tendrá el mismo acceso que tú a todo el panel, con su propio usuario y contraseña.
    </p>

</div>

@endsection
