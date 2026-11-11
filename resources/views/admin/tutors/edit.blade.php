@extends('layouts.admin')

@section('title', 'Editar Tutor')

@section('content')

<div class="max-w-3xl mx-auto">

    <!-- Encabezado -->
    <div class="mb-8">

        <a href="{{ route('admin.tutors.index') }}"
           class="inline-flex items-center gap-1 text-sm text-upp-primary-container hover:underline">
            ← Volver a Gestión de Tutores
        </a>

        <p class="text-xs font-semibold tracking-widest uppercase text-upp-secondary mt-6">
            Administración / <span class="text-upp-primary">Tutores</span>
        </p>

        <h1 class="font-heading text-3xl sm:text-4xl font-bold text-upp-primary mt-2">
            Editar Tutor
        </h1>

        <p class="text-gray-600 mt-2">
            Modifica los datos de <strong class="text-upp-primary">{{ $tutor->name }}</strong>.
        </p>

    </div>

    <!-- Formulario -->
    <div class="bg-white rounded-xl shadow-sm p-6 sm:p-8">

        <form action="{{ route('admin.tutors.update', $tutor) }}" method="POST" autocomplete="off">

            @csrf
            @method('PUT')

            @include('admin.tutors.partials.form', ['tutor' => $tutor])

            <!-- Botones -->
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-6 border-t border-upp-surface-high">

                <a href="{{ route('admin.tutors.index') }}"
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

    <!-- Alumnos asignados -->
    <div class="bg-white rounded-xl shadow-sm mt-6 overflow-hidden">

        <div class="px-6 py-4 border-b border-upp-surface-high flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <h2 class="font-heading text-lg font-bold text-upp-primary">Alumnos asignados</h2>
                <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-upp-surface-high text-upp-primary-container">
                    {{ $students->count() }}
                </span>
            </div>
            <a href="{{ route('admin.tutors.students', $tutor) }}"
               class="px-3 py-1.5 rounded-lg bg-upp-surface-high text-sm text-upp-primary-container font-medium hover:bg-upp-secondary-container transition">
                Asignar alumnos
            </a>
        </div>

        @forelse ($students as $student)
            <div class="px-6 py-4 border-b border-upp-surface-high last:border-0 flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="font-semibold text-upp-primary">{{ $student->name }}</p>
                    <p class="text-sm text-gray-600 mt-1">
                        Matrícula {{ $student->matricula ?? '—' }}
                        · {{ $student->carrera ?? 'Sin carrera' }}
                        @if ($student->cuatrimestre)
                            · {{ is_numeric($student->cuatrimestre) ? $student->cuatrimestre . '° cuatrimestre' : 'Cuatrimestre: ' . $student->cuatrimestre }}
                        @endif
                    </p>
                </div>
                <form action="{{ route('admin.tutors.students.unassign', [$tutor, $student]) }}" method="POST"
                      onsubmit="return confirm(@js("¿Quitar a {$student->name} de los alumnos de {$tutor->name}?"))">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="shrink-0 px-3 py-1.5 rounded-lg text-sm text-red-600 font-medium hover:bg-red-50 transition">
                        Quitar
                    </button>
                </form>
            </div>
        @empty
            <p class="px-6 py-6 text-sm text-gray-500">
                Todavía no tiene alumnos. Los alumnos lo eligen al registrarse, o puedes
                <a href="{{ route('admin.tutors.students', $tutor) }}" class="text-upp-primary-container font-medium hover:underline">asignárselos aquí</a>.
            </p>
        @endforelse

    </div>

</div>

@endsection
