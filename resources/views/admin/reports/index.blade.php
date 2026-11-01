@extends('layouts.admin')

@section('title', 'Reportes')

@section('content')

@php
    [$reportTitle, $reportDescription] = $reports[$type];
    $hasFilters = array_filter($filters) !== [];

    // Color de los valores de la columna "Estado"
    $statusStyles = [
        'Acreditado' => 'bg-green-100 text-green-800',
        'En progreso' => 'bg-upp-surface-high text-upp-primary-container',
        'Sin horas' => 'bg-gray-100 text-gray-600',
        'Finalizada' => 'bg-gray-100 text-gray-600',
        'En curso' => 'bg-green-100 text-green-800',
        'Programada' => 'bg-upp-surface-high text-upp-primary-container',
    ];

    // Ícono de cada reporte (trazos de Heroicons)
    $icons = [
        'inscripciones' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        'asistencias' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'talleres' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'ponentes' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
        'horas' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'certificados' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
    ];
@endphp

    <!-- Encabezado -->
    <div class="mb-8">
        <p class="text-xs font-semibold tracking-widest uppercase text-upp-secondary">
            Administración / <span class="text-upp-primary">Reportes</span>
        </p>

        <h1 class="font-heading text-3xl sm:text-4xl font-bold text-upp-primary mt-2">
            Reportes del Evento
        </h1>

        <p class="text-gray-600 mt-2">
            Consulta la información de Aldea Tecnológica y descárgala en CSV para abrirla en Excel.
        </p>
    </div>

    <!-- Totales del evento -->
    <div class="grid gap-4 grid-cols-2 xl:grid-cols-4 mb-8">

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Inscripciones activas</p>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ number_format($summary['inscripciones']) }}</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Asistencias</p>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ number_format($summary['asistencias']) }}</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Horas acreditadas</p>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $summary['horas'] }}</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Certificados emitidos</p>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ number_format($summary['certificados']) }}</p>
        </div>

    </div>

    <!-- Selector de reporte -->
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 mb-6">
        @foreach ($reports as $key => [$title, $description])
            <a href="{{ route('admin.reports.index', ['reporte' => $key]) }}"
               class="flex items-start gap-3 rounded-xl p-4 transition
                      {{ $type === $key ? 'bg-upp-primary-container text-white shadow-sm' : 'bg-white text-gray-700 shadow-sm hover:bg-upp-surface-low' }}">
                <span class="p-2 rounded-lg shrink-0 {{ $type === $key ? 'bg-white/15' : 'bg-upp-surface-high text-upp-primary-container' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$key] }}" />
                    </svg>
                </span>
                <span>
                    <span class="block font-semibold {{ $type === $key ? 'text-white' : 'text-upp-primary' }}">{{ $title }}</span>
                    <span class="block text-xs mt-0.5 {{ $type === $key ? 'text-white/80' : 'text-gray-500' }}">{{ $description }}</span>
                </span>
            </a>
        @endforeach
    </div>

    <!-- Reporte -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">

        <div class="px-4 lg:px-6 py-4 border-b border-upp-surface-high">

            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between mb-4">
                <div>
                    <h2 class="font-heading text-lg font-bold text-upp-primary">{{ $reportTitle }}</h2>
                    <p class="text-sm text-gray-500">{{ $reportDescription }}</p>
                </div>

                <a href="{{ route('admin.reports.export', ['reporte' => $type] + array_filter($filters)) }}"
                   class="inline-flex items-center justify-center gap-2 shrink-0 whitespace-nowrap bg-upp-primary-container hover:bg-upp-primary text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Descargar CSV
                </a>
            </div>

            <!-- Filtros -->
            <form action="{{ route('admin.reports.index') }}" method="GET" class="flex flex-col gap-2 sm:flex-row">
                <input type="hidden" name="reporte" value="{{ $type }}">

                @if ($type === 'horas')
                    <select name="carrera" class="flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-upp-primary-container">
                        <option value="">Todas las carreras</option>
                        @foreach ($carreras as $carrera)
                            <option value="{{ $carrera }}" @selected($filters['carrera'] === $carrera)>{{ $carrera }}</option>
                        @endforeach
                    </select>
                @else
                    <select name="dia" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-upp-primary-container">
                        <option value="">Todos los días</option>
                        @foreach ($eventDays as $value => $label)
                            <option value="{{ $value }}" @selected($filters['dia'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <select name="tipo" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-upp-primary-container">
                        <option value="">Todos los tipos</option>
                        @foreach ($types as $activityType)
                            <option value="{{ $activityType->id }}" @selected($filters['tipo'] == $activityType->id)>{{ $activityType->name }}</option>
                        @endforeach
                    </select>
                @endif

                <div class="flex gap-2">
                    <button type="submit"
                            class="flex-1 sm:flex-none px-4 py-2 rounded-lg bg-upp-surface-high text-upp-primary-container text-sm font-medium hover:bg-upp-secondary-container transition">
                        Filtrar
                    </button>

                    @if ($hasFilters)
                        <a href="{{ route('admin.reports.index', ['reporte' => $type]) }}"
                           class="px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-upp-surface-low transition">
                            Limpiar
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">

                <thead class="bg-upp-surface-low text-upp-secondary text-xs uppercase tracking-wider">
                    <tr>
                        @foreach ($columns as [$label, $numeric])
                            <th class="px-4 lg:px-6 py-3 font-semibold whitespace-nowrap {{ $numeric ? 'text-right' : '' }}">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>

                <tbody class="divide-y divide-upp-surface-high">
                    @forelse ($rows as $row)
                        <tr class="hover:bg-upp-surface/60">
                            @foreach ($row as $i => $cell)
                                @php [$label, $numeric] = $columns[$i]; @endphp
                                <td class="px-4 lg:px-6 py-3 {{ $numeric ? 'text-right tabular-nums whitespace-nowrap' : '' }} {{ $i === 0 ? 'font-semibold text-upp-primary min-w-[12rem]' : 'text-gray-700' }}">
                                    @if ($label === 'Estado' && isset($statusStyles[$cell]))
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap {{ $statusStyles[$cell] }}">{{ $cell }}</span>
                                    @elseif ($label === 'Folio')
                                        <span class="font-mono text-xs">{{ $cell }}</span>
                                    @else
                                        {{ $cell }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) }}" class="px-4 lg:px-6 py-12 text-center text-gray-500">
                                {{ $hasFilters ? 'No hay datos con esos filtros.' : 'Todavía no hay datos para este reporte.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        <p class="px-4 lg:px-6 py-3 border-t border-upp-surface-high text-xs text-gray-500">
            {{ $rows->total() }} {{ $rows->total() === 1 ? 'fila' : 'filas' }}. El CSV incluye todas las filas con los filtros aplicados.
        </p>

    </div>

    <!-- Paginación -->
    <div class="mt-6">
        {{ $rows->links() }}
    </div>

@endsection
