@extends('layouts.admin')

@section('title', 'Asignar Alumnos')

@section('content')

@php
    $field = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-upp-primary-container';
    $total = $students->total();
    $hasFilters = $filters['q'] !== '' || $filters['carrera'] || $filters['cuatrimestre'] || $filters['mostrar'] === 'todos';

    // Texto del filtro para confirmar la asignación en bloque
    $scope = collect([
        $filters['carrera'],
        $filters['cuatrimestre'] ? (is_numeric($filters['cuatrimestre']) ? $filters['cuatrimestre'] . '° cuatrimestre' : $filters['cuatrimestre']) : null,
        $filters['q'] !== '' ? "«{$filters['q']}»" : null,
    ])->filter()->implode(', ');
    $confirmAll = "¿Asignar los {$total} alumnos" . ($scope ? " de {$scope}" : '') . " a {$tutor->name}?"
        . ($filters['mostrar'] === 'todos' ? ' Los que ya tienen otro tutor cambiarán a este.' : '');
@endphp

    <!-- Encabezado -->
    <div class="mb-8">

        <a href="{{ route('admin.tutors.index') }}"
           class="inline-flex items-center gap-1 text-sm text-upp-primary-container hover:underline">
            ← Volver a Gestión de Tutores
        </a>

        <p class="text-xs font-semibold tracking-widest uppercase text-upp-secondary mt-6">
            Administración / Tutores / <span class="text-upp-primary">Asignar alumnos</span>
        </p>

        <h1 class="font-heading text-3xl sm:text-4xl font-bold text-upp-primary mt-2">
            Asignar alumnos
        </h1>

        <p class="text-gray-600 mt-2">
            Tutor: <strong class="text-upp-primary">{{ $tutor->name }}</strong>
            @if ($tutor->carrera)
                · {{ $tutor->carrera }}
            @endif
            · tiene {{ $assignedCount }} {{ $assignedCount === 1 ? 'alumno asignado' : 'alumnos asignados' }}
            (<a href="{{ route('admin.tutors.edit', $tutor) }}" class="text-upp-primary-container font-medium hover:underline">ver o quitar</a>).
        </p>

    </div>

    <!-- Filtros -->
    <form action="{{ route('admin.tutors.students', $tutor) }}" method="GET" data-filtro-vivo
          class="bg-white rounded-xl shadow-sm p-4 lg:p-5 mb-6 grid gap-3 md:grid-cols-2 xl:grid-cols-[1.4fr_1.6fr_0.8fr_0.9fr_auto] xl:items-end">

        <div>
            <label for="q" class="block text-xs font-semibold text-gray-600 mb-1">Buscar</label>
            <input type="search" id="q" name="q" value="{{ $filters['q'] }}" placeholder="Nombre, matrícula o correo..." class="{{ $field }}">
        </div>

        <div>
            <label for="carrera" class="block text-xs font-semibold text-gray-600 mb-1">Carrera</label>
            <select id="carrera" name="carrera" class="{{ $field }}">
                <option value="">Todas las carreras</option>
                @foreach (config('aldea.carreras') as $carrera)
                    <option value="{{ $carrera }}" @selected($filters['carrera'] === $carrera)>{{ $carrera }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="cuatrimestre" class="block text-xs font-semibold text-gray-600 mb-1">Cuatrimestre</label>
            <select id="cuatrimestre" name="cuatrimestre" class="{{ $field }}">
                <option value="">Todos</option>
                @foreach (config('aldea.cuatrimestres') as $c)
                    <option value="{{ $c }}" @selected($filters['cuatrimestre'] === $c)>{{ is_numeric($c) ? $c . '°' : $c }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="mostrar" class="block text-xs font-semibold text-gray-600 mb-1">Mostrar</label>
            <select id="mostrar" name="mostrar" class="{{ $field }}">
                <option value="sin_tutor" @selected($filters['mostrar'] === 'sin_tutor')>Solo sin tutor</option>
                <option value="todos" @selected($filters['mostrar'] === 'todos')>Todos los alumnos</option>
            </select>
        </div>

        <div class="flex gap-2 md:col-span-2 xl:col-span-1">
            <button type="submit"
                    class="flex-1 xl:flex-none px-4 py-2 rounded-lg bg-upp-surface-high text-upp-primary-container text-sm font-medium hover:bg-upp-secondary-container transition">
                Filtrar
            </button>
            <span data-resultados="limpiar" class="contents">
            @if ($hasFilters)
                <a href="{{ route('admin.tutors.students', $tutor) }}"
                   class="px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-upp-surface-low transition">
                    Limpiar
                </a>
            @endif
            </span>
        </div>

    </form>

    <!-- Resultados -->
    <form action="{{ route('admin.tutors.students.assign', $tutor) }}" method="POST" id="assign-form" data-resultados="alumnos"
          class="bg-white rounded-xl shadow-sm overflow-hidden">

        @csrf
        @foreach (array_filter($filters) as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach

        <div class="px-4 lg:px-6 py-4 border-b border-upp-surface-high flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="font-heading text-lg font-bold text-upp-primary">
                    {{ $filters['mostrar'] === 'todos' ? 'Alumnos' : 'Alumnos sin tutor' }}
                </h2>
                <p class="text-sm text-gray-500">
                    {{ $total }} {{ $total === 1 ? 'coincide' : 'coinciden' }} con el filtro. Marca los que quieras asignar.
                </p>
            </div>

            @if ($total > 0)
                <div class="flex flex-col sm:flex-row gap-2">
                    <button type="submit" id="assign-selected" disabled
                            class="px-4 py-2 rounded-lg bg-upp-primary-container text-white text-sm font-medium shadow-sm transition hover:bg-upp-primary-dark disabled:opacity-40 disabled:cursor-not-allowed">
                        Asignar seleccionados (<span id="selected-count">0</span>)
                    </button>
                    <button type="submit" name="todos" value="1"
                            onclick="return confirm(@js($confirmAll))"
                            class="px-4 py-2 rounded-lg border border-upp-primary-container text-upp-primary-container text-sm font-medium hover:bg-upp-surface-low transition">
                        Asignar los {{ $total }} del filtro
                    </button>
                </div>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">

                <thead class="bg-upp-surface-low text-upp-secondary text-xs uppercase tracking-wider">
                    <tr>
                        <th class="pl-4 lg:pl-6 pr-2 py-3 w-10">
                            <input type="checkbox" id="select-all" aria-label="Seleccionar todos los de esta página"
                                   class="rounded border-gray-300 text-upp-primary-container focus:ring-upp-primary-container">
                        </th>
                        <th class="px-4 py-3 font-semibold">Alumno</th>
                        <th class="px-4 py-3 font-semibold">Carrera</th>
                        <th class="px-4 py-3 font-semibold">Cuatrimestre</th>
                        <th class="px-4 lg:pr-6 py-3 font-semibold">Tutor actual</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-upp-surface-high">
                    @forelse ($students as $student)
                        <tr class="hover:bg-upp-surface/60">
                            <td class="pl-4 lg:pl-6 pr-2 py-3">
                                <input type="checkbox" name="alumnos[]" value="{{ $student->id }}" id="alumno-{{ $student->id }}"
                                       class="student-check rounded border-gray-300 text-upp-primary-container focus:ring-upp-primary-container">
                            </td>
                            <td class="px-4 py-3">
                                <label for="alumno-{{ $student->id }}" class="cursor-pointer">
                                    <span class="block font-semibold text-upp-primary">{{ $student->name }}</span>
                                    <span class="block text-xs text-gray-500">Matrícula {{ $student->matricula ?? '—' }} · {{ $student->email }}</span>
                                </label>
                            </td>
                            <td class="px-4 py-3 text-gray-600 max-w-[16rem]">{{ $student->carrera ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                {{ is_numeric($student->cuatrimestre) ? $student->cuatrimestre . '°' : ($student->cuatrimestre ?? '—') }}
                            </td>
                            <td class="px-4 lg:pr-6 py-3 whitespace-nowrap">
                                @if ($student->tutor)
                                    <span class="text-gray-700">{{ $student->tutor->name }}</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-medium bg-amber-50 text-amber-700">Sin tutor</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 lg:px-6 py-12 text-center text-gray-500">
                                @if ($hasFilters)
                                    Ningún alumno coincide con el filtro.
                                @else
                                    Todos los alumnos registrados ya tienen tutor.
                                    <a href="{{ route('admin.tutors.students', [$tutor, 'mostrar' => 'todos']) }}" class="text-upp-primary-container font-medium hover:underline">
                                        Ver todos para reasignar.
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

    </form>

    <!-- Paginación -->
    <div class="mt-6" data-resultados="paginacion">
        {{ $students->links() }}
    </div>

@push('scripts')
    <script>
        (() => {
            // Se escucha en el documento (no en cada casilla) porque la búsqueda
            // mientras escribes reemplaza la tabla: así sigue funcionando.
            // Habilita "Asignar seleccionados" solo cuando hay alumnos marcados.
            const refresh = () => {
                const checks = [...document.querySelectorAll('.student-check')];
                const selectAll = document.getElementById('select-all');
                const button = document.getElementById('assign-selected');
                const counter = document.getElementById('selected-count');

                if (!button || !selectAll) return;

                const selected = checks.filter((check) => check.checked).length;
                counter.textContent = selected;
                button.disabled = selected === 0;
                selectAll.checked = selected > 0 && selected === checks.length;
                selectAll.indeterminate = selected > 0 && selected < checks.length;
            };

            document.addEventListener('change', (event) => {
                if (event.target.id === 'select-all') {
                    document.querySelectorAll('.student-check').forEach((check) => { check.checked = event.target.checked; });
                    refresh();
                } else if (event.target.classList?.contains('student-check')) {
                    refresh();
                }
            });

            refresh();
        })();
    </script>
@endpush

@endsection
