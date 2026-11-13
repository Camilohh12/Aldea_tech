<!DOCTYPE html>
<html lang="es-MX" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cronograma de actividades · Aldea Tecnológica UPP 2026</title>
    <meta name="description" content="Todas las actividades de la Aldea Tecnológica UPP 2026 ordenadas por día y horario.">
    <link rel="icon" type="image/png" href="{{ asset('img/escudo-upp.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/admin.css'])
</head>

<body class="bg-upp-surface font-sans text-[#1C1B1F] antialiased">

@php
    $esAlumno = auth()->user()?->hasRole('alumno') ?? false;
    // Dónde se inscribe: el catálogo del alumno en ese día o, sin sesión, el login de alumnos
    $inscripcionUrl = fn (string $fecha) => $esAlumno
        ? route('alumno.catalogo', ['fecha' => $fecha])
        : (auth()->check() ? route('dashboard') : route('login', ['perfil' => 'alumno']));

    $chip = fn (bool $activo) => $activo
        ? 'bg-upp-primary-container text-white shadow-sm'
        : 'bg-white text-upp-primary-container ring-1 ring-upp-surface-high hover:bg-upp-surface-low';
@endphp

    <!-- Barra superior -->
    <header class="sticky top-0 z-30 bg-upp-primary text-white shadow-md">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
            <a href="{{ route('home') }}" class="flex items-center gap-3 shrink-0">
                <img src="{{ asset('img/escudo-upp.png') }}" alt="Escudo UPP" class="w-10 h-10 rounded-lg bg-white/10 p-1 object-contain">
                <span class="leading-tight">
                    <span class="block font-heading font-bold">Aldea Tecnológica UPP</span>
                    <span class="hidden sm:block text-xs text-upp-primary-fixed-dim">Cronograma de actividades</span>
                </span>
            </a>

            <div class="flex items-center gap-2">
                <a href="{{ route('home') }}" class="hidden sm:inline-flex px-4 py-2 rounded-lg text-sm font-medium text-white hover:bg-white/10 transition">Inicio</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="whitespace-nowrap px-4 py-2 rounded-lg bg-white text-upp-primary text-sm font-semibold hover:bg-upp-surface-high transition">Ir a mi panel</a>
                @else
                    <a href="{{ route('login') }}" class="whitespace-nowrap px-4 py-2 rounded-lg bg-white text-upp-primary text-sm font-semibold hover:bg-upp-surface-high transition">Iniciar sesión</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8 sm:py-10">

        <!-- Título -->
        <p class="text-xs font-semibold tracking-widest uppercase text-upp-secondary">24 al 26 de noviembre de 2026</p>
        <h1 class="font-heading text-3xl sm:text-4xl font-extrabold text-upp-primary mt-2">Cronograma de actividades</h1>
        <p class="text-gray-600 mt-2">
            {{ $total === 1 ? '1 actividad' : $total.' actividades' }} en orden de horario.
            @if ($hayHoy) Las de hoy aparecen primero. @endif
        </p>

        <!-- Filtro por día -->
        <nav class="sticky top-16 z-20 -mx-4 sm:mx-0 mt-6 bg-upp-surface/95 backdrop-blur px-4 sm:px-0 py-3" aria-label="Días del evento">
            <div class="flex gap-2 overflow-x-auto pb-1">
                <a href="{{ route('cronograma') }}" @if (! $seleccionado) aria-current="page" @endif
                   class="shrink-0 whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold transition {{ $chip(! $seleccionado) }}">
                    Todos los días
                </a>
                @foreach ($dias as $dia)
                    <a href="{{ route('cronograma', ['dia' => $dia->fecha]) }}" @if ($seleccionado === $dia->fecha) aria-current="page" @endif
                       class="shrink-0 whitespace-nowrap inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition {{ $chip($seleccionado === $dia->fecha) }}">
                        {{ $dia->corto }}
                        @if ($dia->esHoy)
                            <span class="rounded-full bg-emerald-500 px-2 py-0.5 text-[11px] font-bold text-white">Hoy</span>
                        @endif
                        <span class="text-xs font-medium opacity-70">{{ $dia->actividades->count() }}</span>
                    </a>
                @endforeach
            </div>
        </nav>

        <!-- Días -->
        <div class="mt-4 space-y-10">
            @foreach ($visibles as $dia)
                <section aria-labelledby="dia-{{ $dia->fecha }}"
                         @class(['rounded-2xl', 'bg-white ring-2 ring-emerald-400 shadow-sm p-4 sm:p-6' => $dia->esHoy])>

                    <div class="flex flex-wrap items-center gap-3 mb-5">
                        <h2 id="dia-{{ $dia->fecha }}" class="font-heading text-xl sm:text-2xl font-bold text-upp-primary">
                            @if ($dia->esHoy) Hoy · @endif{{ $dia->largo }}
                        </h2>
                        @if ($dia->esHoy)
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">Actividades de hoy</span>
                        @endif
                        <span class="text-sm text-gray-500">
                            {{ $dia->actividades->count() === 1 ? '1 actividad' : $dia->actividades->count().' actividades' }}
                        </span>
                    </div>

                    @if ($dia->actividades->isEmpty())
                        <p class="rounded-xl border border-dashed border-upp-surface-high bg-white px-4 py-8 text-center text-gray-500">
                            Aún no hay actividades programadas para este día.
                        </p>
                    @else
                        <!-- Línea de tiempo -->
                        <ol class="relative ml-2 sm:ml-[5.5rem] border-l-2 border-upp-surface-high">
                            @foreach ($dia->actividades as $activity)
                                @php
                                    $enCurso = $activity->isInProgress();
                                    $termino = $activity->hasEnded();
                                    $lugares = $activity->availableSpots();
                                @endphp
                                <li class="relative pl-6 pb-6 last:pb-0">
                                    <!-- Punto -->
                                    <span @class([
                                        'absolute -left-[9px] top-1.5 h-4 w-4 rounded-full ring-4 ring-upp-surface',
                                        'bg-emerald-500' => $enCurso,
                                        'bg-gray-300' => $termino,
                                        'bg-upp-primary-container' => ! $enCurso && ! $termino,
                                    ])></span>

                                    <!-- Horario (a la izquierda en pantallas grandes) -->
                                    <p class="sm:absolute sm:-left-[6rem] sm:top-0.5 sm:w-20 sm:text-right font-heading font-bold text-upp-primary leading-tight">
                                        {{ $activity->start_time ? substr($activity->start_time, 0, 5) : 'Por definir' }}
                                        @if ($activity->end_time)
                                            <span class="sm:block text-xs font-medium text-gray-500">a {{ substr($activity->end_time, 0, 5) }}</span>
                                        @endif
                                    </p>

                                    <article @class([
                                        'mt-1 sm:mt-0 rounded-xl bg-white p-4 shadow-sm ring-1',
                                        'ring-emerald-300' => $enCurso,
                                        'ring-upp-surface-high' => ! $enCurso,
                                        'opacity-70' => $termino,
                                    ])>
                                        <div class="flex flex-wrap items-center gap-2">
                                            @if ($activity->activityType)
                                                <span class="rounded-md bg-upp-surface-high px-2 py-0.5 text-xs font-semibold text-upp-primary-container">{{ $activity->activityType->name }}</span>
                                            @endif
                                            @if ($enCurso)
                                                <span class="inline-flex items-center gap-1.5 rounded-md bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-800">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span> En curso
                                                </span>
                                            @elseif ($termino)
                                                <span class="rounded-md bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600">Finalizada</span>
                                            @elseif ($lugares !== null && $lugares <= 0)
                                                <span class="rounded-md bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">Cupo lleno</span>
                                            @elseif ($lugares !== null)
                                                <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                                                    {{ $lugares === 1 ? '1 lugar' : $lugares.' lugares' }}
                                                </span>
                                            @endif
                                        </div>

                                        <h3 class="font-heading text-lg font-bold text-upp-primary mt-2">{{ $activity->title }}</h3>

                                        <p class="text-sm text-gray-600 mt-1">
                                            {{ $activity->room?->name ?? 'Aula por definir' }}
                                            @if ($activity->speakers->isNotEmpty())
                                                · {{ $activity->speakers->pluck('name')->join(', ', ' y ') }}
                                            @endif
                                        </p>

                                        @if (! $termino && ! $enCurso)
                                            <a href="{{ $inscripcionUrl($dia->fecha) }}"
                                               class="inline-flex items-center gap-1 mt-3 text-sm font-semibold text-upp-primary-container hover:underline">
                                                {{ $esAlumno ? 'Inscribirme' : 'Inicia sesión para inscribirte' }} →
                                            </a>
                                        @endif
                                    </article>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </section>
            @endforeach
        </div>

    </main>

    <footer class="bg-upp-primary text-white mt-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="font-heading font-bold">Aldea Tecnológica UPP 2026</p>
            <nav class="flex flex-wrap gap-4 text-sm text-white/80">
                <a href="{{ route('home') }}" class="hover:text-white">Inicio</a>
                <a href="{{ route('home') }}#catalogo" class="hover:text-white">Catálogo</a>
                <a href="{{ route('login') }}" class="hover:text-white">Iniciar sesión</a>
            </nav>
        </div>
    </footer>

</body>
</html>
