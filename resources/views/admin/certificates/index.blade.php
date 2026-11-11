@extends('layouts.admin')

@section('title', 'Certificados de Ponentes')

@section('content')

@php
    // Pestañas de estado con su conteo
    $tabs = [
        'todos' => ['Todos', $counts['todos']],
        'listo' => ['Listos para generar', $counts['listo']],
        'emitido' => ['Emitidos', $counts['emitido']],
        'pendiente' => ['Pendientes', $counts['pendiente']],
    ];
@endphp

    <!-- Encabezado -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-8">

        <div>
            <p class="text-xs font-semibold tracking-widest uppercase text-upp-secondary">
                Administración / <span class="text-upp-primary">Certificados</span>
            </p>

            <h1 class="font-heading text-3xl sm:text-4xl font-bold text-upp-primary mt-2">
                Certificados de Ponentes
            </h1>

            <p class="text-gray-600 mt-2">
                Genera las constancias de quienes impartieron talleres, cursos, conferencias y foros.
            </p>
        </div>

        <a href="{{ route('admin.certificates.template') }}"
           class="inline-flex items-center justify-center gap-2 shrink-0 whitespace-nowrap bg-upp-primary-container hover:bg-upp-primary-dark text-white px-5 py-3 rounded-lg font-medium shadow-sm transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Editar plantilla
        </a>

    </div>

    <!-- Tarjetas de resumen -->
    <div class="grid gap-4 grid-cols-2 xl:grid-cols-4 mb-8">

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Por certificar</p>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $counts['todos'] }}</p>
            <p class="text-sm text-upp-secondary mt-1">Ponentes con actividad</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Listos</p>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $counts['listo'] }}</p>
            <p class="text-sm text-upp-secondary mt-1">Su actividad ya terminó</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Emitidos</p>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $counts['emitido'] }}</p>
            <p class="text-sm text-upp-secondary mt-1">Con folio asignado</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Pendientes</p>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $counts['pendiente'] }}</p>
            <p class="text-sm text-upp-secondary mt-1">Actividad sin terminar</p>
        </div>

    </div>

    @if ($withoutSpeakers > 0)
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between bg-amber-50 border border-amber-200 text-amber-900 px-5 py-3 rounded-xl mb-6 text-sm">
            <p>
                <strong>{{ $withoutSpeakers }} {{ $withoutSpeakers === 1 ? 'actividad no tiene' : 'actividades no tienen' }} ponente</strong>
                y no {{ $withoutSpeakers === 1 ? 'genera' : 'generan' }} certificado.
            </p>
            <a href="{{ route('admin.activities.index') }}" class="font-semibold underline shrink-0">Revisar actividades</a>
        </div>
    @endif

    <!-- Tabla -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">

        <div class="px-4 lg:px-6 py-4 border-b border-upp-surface-high flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">

            <!-- Pestañas -->
            <div class="flex flex-wrap gap-2" data-resultados="pestanas">
                @foreach ($tabs as $key => [$label, $total])
                    <a href="{{ route('admin.certificates.index', ['estado' => $key, 'q' => $search ?: null]) }}"
                       class="px-3 py-1.5 rounded-lg text-sm font-medium transition
                              {{ $estado === $key ? 'bg-upp-primary-container text-white' : 'bg-upp-surface-low text-upp-primary-container hover:bg-upp-surface-high' }}">
                        {{ $label }} <span class="{{ $estado === $key ? 'text-white/80' : 'text-gray-500' }}">({{ $total }})</span>
                    </a>
                @endforeach
            </div>

            <!-- Búsqueda -->
            <form action="{{ route('admin.certificates.index') }}" method="GET" data-filtro-vivo class="flex gap-2 w-full xl:w-auto">
                <input type="hidden" name="estado" value="{{ $estado }}">
                <div class="relative flex-1 xl:w-80">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" />
                    </svg>
                    <input type="search" name="q" value="{{ $search }}"
                           placeholder="Buscar ponente o actividad..."
                           class="w-full rounded-lg border border-gray-300 bg-white pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-upp-primary-container">
                </div>
                <button type="submit"
                        class="px-4 py-2 rounded-lg bg-upp-surface-high text-upp-primary-container text-sm font-medium hover:bg-upp-secondary-container transition">
                    Buscar
                </button>
            </form>
        </div>

        <div class="overflow-x-auto" data-resultados="tabla">
            <table class="w-full text-left text-sm">

                <thead class="bg-upp-surface-low text-upp-secondary text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Ponente / Instructor</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Actividad impartida</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Lugar / Fecha</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Horas</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Estatus</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold text-right">Acción</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-upp-surface-high">
                    @forelse ($rows as $row)
                        <tr class="hover:bg-upp-surface/60 align-top">

                            <td class="px-4 lg:px-6 py-4 min-w-[13rem]">
                                <div class="flex items-center gap-3">
                                    <span class="w-9 h-9 rounded-full bg-upp-surface-high text-upp-primary-container flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ $row->speaker->initials }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-upp-primary">{{ $row->speaker->name }}</p>
                                        @if ($row->speaker->bio)
                                            <p class="text-xs text-gray-500 truncate max-w-[14rem]">{{ Str::limit(strtok($row->speaker->bio, "\n"), 45) }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 lg:px-6 py-4 min-w-[13rem]">
                                <p class="text-gray-800 font-medium">{{ $row->activity->title }}</p>
                                <span class="inline-flex mt-1 px-2 py-0.5 rounded-md text-xs font-medium bg-upp-secondary-container/60 text-upp-primary-container">
                                    {{ $row->activity->activityType?->name ?? 'Sin tipo' }}
                                </span>
                            </td>

                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <p class="text-gray-800">{{ $row->activity->room?->name ?? 'Sin aula' }}</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ $row->activity->date ? ucfirst($row->activity->date->locale('es')->translatedFormat('D j M')) : 'Sin fecha' }}
                                    · {{ $row->activity->timeRange() }}
                                </p>
                            </td>

                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 rounded-md text-xs font-semibold bg-upp-surface-high text-upp-primary-container">
                                    {{ rtrim(rtrim(number_format($row->activity->creditedHours(), 1), '0'), '.') }} h
                                </span>
                            </td>

                            <td class="px-4 lg:px-6 py-4">
                                @if ($row->estado === 'emitido')
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-upp-surface-high text-upp-primary-container whitespace-nowrap">
                                        Emitido
                                    </span>
                                    <p class="font-mono text-[11px] text-gray-500 mt-1 whitespace-nowrap">{{ $row->certificate->folio }}</p>
                                    <p class="text-xs text-gray-400 whitespace-nowrap">el {{ $row->certificate->issued_at->format('d/m/Y') }}</p>
                                @elseif ($row->estado === 'listo')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 whitespace-nowrap">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-600"></span>
                                        Listo para generar
                                    </span>
                                @else
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 whitespace-nowrap">
                                        Pendiente
                                    </span>
                                    <p class="text-xs text-gray-400 mt-1 whitespace-nowrap">Al terminar la actividad</p>
                                @endif
                            </td>

                            <td class="px-4 lg:px-6 py-4 text-right">
                                @if ($row->estado === 'listo')
                                    <a href="{{ route('admin.certificates.download', [$row->activity, $row->speaker]) }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-upp-primary-container hover:bg-upp-primary-dark text-white font-medium transition whitespace-nowrap">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Generar PDF
                                    </a>
                                @elseif ($row->estado === 'emitido')
                                    <a href="{{ route('admin.certificates.download', [$row->activity, $row->speaker]) }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-upp-surface-high text-upp-primary-container font-medium hover:bg-upp-surface-low transition whitespace-nowrap">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        Descargar
                                    </a>
                                @else
                                    <span class="px-3 py-1.5 rounded-lg text-gray-400 cursor-not-allowed whitespace-nowrap"
                                          title="Disponible cuando termine la actividad">
                                        Generar PDF
                                    </span>
                                @endif
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 lg:px-6 py-12 text-center text-gray-500">
                                @if ($search !== '' || $estado !== 'todos')
                                    No hay certificados con ese filtro.
                                @else
                                    Aún no hay ponentes asignados a actividades.
                                    <a href="{{ route('admin.activities.index') }}" class="text-upp-primary-container font-medium hover:underline">Ir a la programación.</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

    </div>

    <!-- Paginación -->
    <div class="mt-6" data-resultados="paginacion">
        {{ $rows->links() }}
    </div>

    <!-- Nota de validez -->
    <div class="flex items-start gap-4 bg-upp-surface-low border border-upp-surface-high rounded-xl p-5 mt-6">
        <span class="p-2 rounded-lg bg-white text-upp-primary-container shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
        </span>
        <div class="text-sm">
            <p class="font-semibold text-upp-primary">Folio único por certificado</p>
            <p class="text-gray-600 mt-1">
                Al generar un certificado se le asigna un folio firmado con la clave de la aplicación; si se vuelve a descargar, conserva el mismo folio y fecha de emisión.
            </p>
        </div>
    </div>

@endsection
