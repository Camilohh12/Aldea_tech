@extends('layouts.admin')

@section('title', 'Certificados de Ponentes')

@section('content')

    <!-- Encabezado -->
    <div class="mb-8">
        <p class="text-xs font-semibold tracking-widest uppercase text-upp-secondary">
            Administración / <span class="text-upp-primary">Certificados</span>
        </p>

        <h1 class="font-heading text-3xl sm:text-4xl font-bold text-upp-primary mt-2">
            Constancias de Ponentes
        </h1>

        <p class="text-gray-600 mt-2">
            Genera en PDF la constancia de cada ponente. Se habilita cuando termina su actividad.
        </p>
    </div>

    <!-- Tarjetas de resumen -->
    <div class="grid gap-4 sm:grid-cols-3 mb-8">
        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Listas para generar</p>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $totalConstancias }}</p>
            <p class="text-sm text-upp-secondary mt-1">Una por ponente en actividades terminadas</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Por realizarse</p>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $proximas->count() }}</p>
            <p class="text-sm text-upp-secondary mt-1">Actividades con ponente asignado</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border-l-4 border-amber-400 p-5">
            <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Sin ponente</p>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $sinPonente->count() }}</p>
            <p class="text-sm text-upp-secondary mt-1">Asígnalo en Talleres para generar su constancia</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-4 lg:px-6 py-4 border-b border-upp-surface-high flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <h2 class="font-heading text-lg font-bold text-upp-primary">Constancias por actividad</h2>

            <form action="{{ route('admin.constancias') }}" method="GET" class="flex gap-2 w-full md:w-auto">
                <div class="relative flex-1 md:w-80">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" />
                    </svg>
                    <input type="search" name="q" value="{{ $q }}" placeholder="Buscar por actividad o ponente..."
                           class="w-full rounded-lg border border-gray-300 bg-white pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-upp-primary-container">
                </div>
                <button type="submit" class="px-4 py-2 rounded-lg bg-upp-surface-high text-upp-primary-container text-sm font-medium hover:bg-upp-secondary-container transition">
                    Buscar
                </button>
                @if ($q !== '')
                    <a href="{{ route('admin.constancias') }}" class="px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-upp-surface-low transition">Limpiar</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-upp-surface-low text-upp-secondary text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Actividad</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Ponente</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Asistentes</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Folio</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold text-right">Constancia</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-upp-surface-high">
                    @forelse ($listas as $a)
                        @foreach ($a->speakers as $speaker)
                            <tr class="hover:bg-upp-surface-low/60">
                                @if ($loop->first)
                                    <td class="px-4 lg:px-6 py-4 align-top" rowspan="{{ $a->speakers->count() }}">
                                        <p class="font-semibold text-[#1C1B1F]">{{ $a->title }}</p>
                                        <p class="text-xs text-upp-secondary mt-0.5">
                                            {{ $a->activityType?->name ?? 'Actividad' }} ·
                                            {{ \App\Models\Activity::shortDate($a->date) ?? 'Sin fecha' }} · {{ $a->timeRange() }} h
                                        </p>
                                    </td>
                                @endif
                                <td class="px-4 lg:px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="w-9 h-9 rounded-full bg-upp-surface-high text-upp-primary-container flex items-center justify-center text-xs font-bold shrink-0">{{ $speaker->initials }}</span>
                                        <div>
                                            <p class="font-medium">{{ $speaker->name }}</p>
                                            @if ($speaker->bio)
                                                <p class="text-xs text-gray-500">{{ \Illuminate\Support\Str::limit(strtok($speaker->bio, "\n"), 45) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                @if ($loop->first)
                                    <td class="px-4 lg:px-6 py-4 align-top" rowspan="{{ $a->speakers->count() }}">{{ $a->asistentes }}</td>
                                @endif
                                <td class="px-4 lg:px-6 py-4 font-mono text-xs text-gray-500 whitespace-nowrap">{{ $a->speakerCertificateFolio($speaker) }}</td>
                                <td class="px-4 lg:px-6 py-4 text-right">
                                    <a href="{{ route('admin.constancias.pdf', [$a, $speaker]) }}"
                                       class="inline-flex items-center gap-2 whitespace-nowrap rounded-lg bg-upp-primary-container hover:bg-upp-primary px-4 py-2 text-sm font-medium text-white shadow-sm transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v11m0 0l-4-4m4 4l4-4M5 20h14" /></svg>
                                        Generar PDF
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                {{ $q !== '' ? 'Sin resultados para la búsqueda.' : 'Todavía no hay actividades terminadas con ponente asignado.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($proximas->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mt-8">
            <div class="px-4 lg:px-6 py-4 border-b border-upp-surface-high">
                <h2 class="font-heading text-lg font-bold text-upp-primary">Por realizarse</h2>
                <p class="text-sm text-gray-500">La constancia se habilita al terminar la actividad.</p>
            </div>
            <ul class="divide-y divide-upp-surface-high text-sm">
                @foreach ($proximas as $a)
                    <li class="px-4 lg:px-6 py-3 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-medium">{{ $a->title }}</p>
                            <p class="text-xs text-gray-500">{{ $a->speakerNames() }}</p>
                        </div>
                        <span class="text-xs text-upp-secondary whitespace-nowrap">
                            {{ \App\Models\Activity::shortDate($a->date) ?? 'Sin fecha' }} · {{ $a->timeRange() }} h
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($sinPonente->isNotEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 mt-8">
            <h2 class="font-heading font-bold text-amber-800">Actividades sin ponente asignado</h2>
            <ul class="mt-2 text-sm text-amber-900 list-disc pl-5">
                @foreach ($sinPonente as $a)
                    <li>{{ $a->title }}</li>
                @endforeach
            </ul>
        </div>
    @endif

@endsection
