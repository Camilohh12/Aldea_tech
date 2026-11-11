<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aldea Tecnológica UPP 2026</title>
    <meta name="description" content="Talleres, cursos, conferencias y foros de la Universidad Politécnica de Pachuca. Del 24 al 26 de noviembre de 2026.">
    <link rel="icon" type="image/png" href="{{ asset('img/escudo-upp.png') }}">

    <!-- Fuentes del prototipo: Inter (texto) y Manrope (títulos) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Estilos compilados con Vite (colores institucionales en tailwind.config.js), sin depender de un CDN -->
    @vite(['resources/css/admin.css', 'resources/js/filtros-limpios.js'])
</head>

<body class="bg-upp-surface font-sans text-[#1C1B1F] antialiased">

@php
    // A dónde lleva cada acceso: el login de cada perfil o, con sesión, el panel
    $loggedIn = auth()->check();
    $alumnoUrl = route('login', ['perfil' => 'alumno']);
    $docenteUrl = route('login', ['perfil' => 'docente']);
    $adminUrl = route('login', ['perfil' => 'admin']);
    $enrollUrl = $loggedIn
        ? (Route::has('alumno.catalogo') ? route('alumno.catalogo') : route('dashboard'))
        : $alumnoUrl;

    $shortDate = fn ($activity) => ucfirst($activity->date->locale('es')->translatedFormat('D j \d\e M'));
@endphp

    <!-- Barra superior -->
    <header class="sticky top-0 z-30 bg-upp-primary text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">

            <a href="{{ route('home') }}" class="flex items-center gap-3 shrink-0">
                <img src="{{ asset('img/escudo-upp.png') }}" alt="Escudo UPP" class="w-10 h-10 rounded-lg bg-white/10 p-1 object-contain">
                <span class="leading-tight">
                    <span class="block font-heading font-bold">Aldea Tecnológica UPP</span>
                    <span class="hidden sm:block text-xs text-upp-primary-fixed-dim">Universidad Politécnica de Pachuca</span>
                </span>
            </a>

            <nav class="hidden md:flex items-center gap-1 text-sm font-medium">
                <a href="#inicio" class="px-3 py-2 rounded-lg text-white/85 hover:bg-white/10 hover:text-white">Inicio</a>
                <a href="#catalogo" class="px-3 py-2 rounded-lg text-white/85 hover:bg-white/10 hover:text-white">Catálogo</a>
                <a href="#accesos" class="px-3 py-2 rounded-lg text-white/85 hover:bg-white/10 hover:text-white">Accesos</a>
            </nav>

            <div class="flex items-center gap-2">
                @if ($loggedIn)
                    <a href="{{ route('dashboard') }}"
                       class="px-4 py-2 rounded-lg bg-white text-upp-primary text-sm font-semibold hover:bg-upp-surface-high transition">
                        Ir a mi panel
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="hidden sm:inline-flex px-4 py-2 rounded-lg text-sm font-medium text-white hover:bg-white/10 transition">
                        Iniciar sesión
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                           class="px-4 py-2 rounded-lg bg-white text-upp-primary text-sm font-semibold hover:bg-upp-surface-high transition">
                            Registrarme
                        </a>
                    @endif
                @endif
            </div>
        </div>
    </header>

    <main>

        <!-- Portada -->
        <section id="inicio" class="relative overflow-hidden">
            <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-upp-secondary-container/50 blur-3xl"></div>
            <div class="absolute top-40 -left-24 w-72 h-72 rounded-full bg-upp-primary-fixed-dim/40 blur-3xl"></div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 lg:py-20 grid gap-10 lg:grid-cols-2 lg:items-center">

                <div>
                    <p class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-upp-primary-container shadow-sm ring-1 ring-upp-surface-high">
                        <span class="w-2 h-2 rounded-full bg-upp-primary-container"></span>
                        24 al 26 de noviembre de 2026 · {{ $countdown }}
                    </p>

                    <h1 class="font-heading text-4xl sm:text-5xl lg:text-6xl font-extrabold text-upp-primary leading-tight mt-6">
                        El epicentro de <span class="text-upp-secondary">innovación</span>, código y futuro digital universitario.
                    </h1>

                    <p class="text-lg text-gray-600 mt-5 max-w-xl">
                        Talleres, cursos, conferencias y foros con especialistas de la industria y la academia.
                        Inscríbete, asiste y acredita tus horas.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-3 mt-8">
                        <a href="#catalogo"
                           class="inline-flex items-center justify-center gap-2 bg-upp-primary-container hover:bg-upp-primary-dark text-white px-6 py-3.5 rounded-lg font-semibold shadow-sm transition">
                            Ver el catálogo
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                            </svg>
                        </a>
                        <a href="#accesos"
                           class="inline-flex items-center justify-center gap-2 bg-white hover:bg-upp-surface-low text-upp-primary-container px-6 py-3.5 rounded-lg font-semibold ring-1 ring-upp-surface-high transition">
                            ¿Cómo entro?
                        </a>
                    </div>
                </div>

                <!-- Actividad destacada (la siguiente del programa) -->
                <div class="relative">
                    <div class="rounded-2xl bg-gradient-to-br from-upp-primary via-upp-primary-container to-upp-secondary p-8 text-white shadow-xl">
                        @if ($featured)
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-xs font-semibold uppercase tracking-widest text-upp-primary-fixed-dim">Próxima actividad</span>
                                <span class="px-2.5 py-1 rounded-full bg-white/15 text-xs font-medium">{{ $featured->activityType?->name ?? 'Actividad' }}</span>
                            </div>
                            <h2 class="font-heading text-2xl sm:text-3xl font-bold mt-4">{{ $featured->title }}</h2>
                            @if ($featured->description)
                                <p class="text-white/80 mt-3 text-sm leading-relaxed">{{ Str::limit($featured->description, 140) }}</p>
                            @endif
                            <dl class="grid grid-cols-2 gap-4 mt-6 text-sm">
                                <div>
                                    <dt class="text-white/60 text-xs uppercase tracking-wider">Fecha</dt>
                                    <dd class="font-semibold mt-1">{{ $shortDate($featured) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-white/60 text-xs uppercase tracking-wider">Horario</dt>
                                    <dd class="font-semibold mt-1">{{ $featured->timeRange() }} h</dd>
                                </div>
                                <div>
                                    <dt class="text-white/60 text-xs uppercase tracking-wider">Lugar</dt>
                                    <dd class="font-semibold mt-1">{{ $featured->room?->name ?? 'Por confirmar' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-white/60 text-xs uppercase tracking-wider">Imparte</dt>
                                    <dd class="font-semibold mt-1">{{ $featured->speakers->pluck('name')->join(', ') ?: 'Por confirmar' }}</dd>
                                </div>
                            </dl>
                        @else
                            <div class="flex items-center gap-4">
                                <img src="{{ asset('img/escudo-upp.png') }}" alt="" class="w-16 h-16 rounded-xl bg-white/10 p-2 object-contain">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-widest text-upp-primary-fixed-dim">Edición 2026</p>
                                    <h2 class="font-heading text-2xl font-bold mt-1">Aldea Tecnológica UPP</h2>
                                </div>
                            </div>
                            <p class="text-white/80 mt-5">
                                Muy pronto publicaremos la programación completa. Regístrate para enterarte primero.
                            </p>
                        @endif
                    </div>
                </div>

            </div>

            <!-- Cifras del evento -->
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-14">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ([
                        ['Actividades programadas', number_format($stats['actividades'])],
                        ['Ponentes', number_format($stats['ponentes'])],
                        ['Horas de contenido', $stats['horas']],
                        ['Lugares disponibles', number_format($stats['lugares'])],
                    ] as [$label, $value])
                        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
                            <p class="font-heading text-3xl sm:text-4xl font-bold text-upp-primary">{{ $value }}</p>
                            <p class="text-sm text-upp-secondary mt-1">{{ $label }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Accesos -->
        <section id="accesos" class="bg-white border-y border-upp-surface-high">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">

                <div class="text-center max-w-2xl mx-auto">
                    <p class="text-xs font-semibold tracking-widest uppercase text-upp-secondary">Accesos</p>
                    <h2 class="font-heading text-3xl sm:text-4xl font-bold text-upp-primary mt-2">¿Cómo quieres entrar?</h2>
                    <p class="text-gray-600 mt-3">Elige tu perfil y te llevamos a iniciar sesión.</p>
                </div>

                <div class="grid gap-6 md:grid-cols-2 mt-10 max-w-4xl mx-auto">

                    <!-- Alumno -->
                    <div class="rounded-2xl border border-upp-surface-high bg-upp-surface-low p-7 flex flex-col">
                        <span class="w-12 h-12 rounded-xl bg-upp-primary-container text-white flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0121 18.5c-2.62 1.256-5.6 1.976-9 1.976s-6.38-.72-9-1.976a12.08 12.08 0 012.84-7.922L12 14z" />
                            </svg>
                        </span>
                        <h3 class="font-heading text-xl font-bold text-upp-primary mt-5">Soy alumno</h3>
                        <p class="text-gray-600 mt-2 flex-1">
                            Inscríbete a talleres y conferencias, consulta tu agenda, muestra tu pase QR y acredita tus horas.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-3 mt-6">
                            <a href="{{ $loggedIn ? route('dashboard') : $alumnoUrl }}"
                               class="inline-flex justify-center bg-upp-primary-container hover:bg-upp-primary-dark text-white px-5 py-3 rounded-lg font-semibold transition">
                                {{ $loggedIn ? 'Ir a mi panel' : 'Iniciar sesión' }}
                            </a>
                            @if (! $loggedIn && Route::has('register'))
                                <a href="{{ route('register') }}"
                                   class="inline-flex justify-center bg-white hover:bg-upp-surface-high text-upp-primary-container px-5 py-3 rounded-lg font-semibold ring-1 ring-upp-surface-high transition">
                                    Crear mi cuenta
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Tutor, docente o responsable -->
                    <div class="rounded-2xl border border-upp-surface-high bg-upp-surface-low p-7 flex flex-col">
                        <span class="w-12 h-12 rounded-xl bg-upp-primary-container text-white flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </span>
                        <h3 class="font-heading text-xl font-bold text-upp-primary mt-5">Soy tutor, docente o responsable</h3>
                        <p class="text-gray-600 mt-2 flex-1">
                            Da seguimiento a tus tutorados o pasa lista en las actividades que tienes a tu cargo.
                        </p>
                        <div class="mt-6">
                            <a href="{{ $loggedIn ? route('dashboard') : $docenteUrl }}"
                               class="inline-flex justify-center w-full sm:w-auto bg-upp-primary-container hover:bg-upp-primary-dark text-white px-5 py-3 rounded-lg font-semibold transition">
                                {{ $loggedIn ? 'Ir a mi panel' : 'Iniciar sesión' }}
                            </a>
                            <p class="text-xs text-gray-500 mt-3">Las cuentas de tutores, docentes y responsables las crea la coordinación del evento.</p>
                        </div>
                    </div>

                </div>

                @unless ($loggedIn)
                    <p class="text-center text-sm text-gray-500 mt-8">
                        ¿Eres de la coordinación?
                        <a href="{{ $adminUrl }}" class="font-semibold text-upp-primary-container hover:underline">Acceso administrativo</a>
                    </p>
                @endunless

            </div>
        </section>

        <!-- Catálogo público -->
        <section id="catalogo" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 scroll-mt-16">

            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold tracking-widest uppercase text-upp-secondary">Programa</p>
                    <h2 class="font-heading text-3xl sm:text-4xl font-bold text-upp-primary mt-2">Catálogo de actividades</h2>
                    <p class="text-gray-600 mt-2">Para inscribirte necesitas iniciar sesión como alumno.</p>
                </div>

                <!-- Filtro por día -->
                @if (count($eventDays))
                    <form action="{{ route('home') }}#catalogo" method="GET" class="flex gap-2">
                        @if ($typeId)
                            <input type="hidden" name="tipo" value="{{ $typeId }}">
                        @endif
                        <select name="dia" onchange="this.form.submit()"
                                class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-upp-primary-container">
                            <option value="">Todos los días</option>
                            @foreach ($eventDays as $value => $label)
                                <option value="{{ $value }}" @selected($day === $value)>{{ $label }} de noviembre</option>
                            @endforeach
                        </select>
                        <noscript><button class="px-3 py-2 rounded-lg bg-upp-surface-high text-sm">Filtrar</button></noscript>
                    </form>
                @endif
            </div>

            <!-- Filtro por tipo -->
            <div class="flex flex-wrap gap-2 mt-6">
                <a href="{{ route('home', ['dia' => $day ?: null]) }}#catalogo"
                   class="px-4 py-2 rounded-full text-sm font-medium transition {{ ! $typeId ? 'bg-upp-primary-container text-white' : 'bg-white text-upp-primary-container ring-1 ring-upp-surface-high hover:bg-upp-surface-low' }}">
                    Todas
                </a>
                @foreach ($types as [$type, $count])
                    <a href="{{ route('home', ['tipo' => $type->id, 'dia' => $day ?: null]) }}#catalogo"
                       class="px-4 py-2 rounded-full text-sm font-medium transition {{ (int) $typeId === $type->id ? 'bg-upp-primary-container text-white' : 'bg-white text-upp-primary-container ring-1 ring-upp-surface-high hover:bg-upp-surface-low' }}">
                        {{ $type->name }} <span class="opacity-70">({{ $count }})</span>
                    </a>
                @endforeach
            </div>

            <!-- Tarjetas -->
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 mt-8">
                @forelse ($catalog as $activity)
                    @php
                        $available = $activity->capacity ? max(0, $activity->capacity - (int) $activity->registered_count) : null;
                        $percent = $activity->capacity ? min(100, round($activity->registered_count * 100 / $activity->capacity)) : 0;
                    @endphp
                    <article class="bg-white rounded-2xl shadow-sm overflow-hidden flex flex-col ring-1 ring-upp-surface-high">

                        <div class="bg-gradient-to-br from-upp-primary-container to-upp-secondary px-5 py-4 text-white flex items-center justify-between">
                            <span class="px-2.5 py-1 rounded-full bg-white/15 text-xs font-semibold">{{ $activity->activityType?->name ?? 'Actividad' }}</span>
                            <span class="text-xs font-medium text-white/85">{{ $shortDate($activity) }} · {{ $activity->timeRange() }}</span>
                        </div>

                        <div class="p-5 flex-1 flex flex-col">
                            <h3 class="font-heading text-lg font-bold text-upp-primary leading-snug">{{ $activity->title }}</h3>
                            @if ($activity->description)
                                <p class="text-sm text-gray-600 mt-2">{{ Str::limit($activity->description, 110) }}</p>
                            @endif

                            <div class="mt-4 space-y-2 text-sm text-gray-700">
                                <p class="flex items-start gap-2">
                                    <svg class="w-4 h-4 mt-0.5 text-upp-secondary shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    {{ $activity->speakers->pluck('name')->join(', ') ?: 'Ponente por confirmar' }}
                                </p>
                                <p class="flex items-start gap-2">
                                    <svg class="w-4 h-4 mt-0.5 text-upp-secondary shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    {{ $activity->room?->name ?? 'Lugar por confirmar' }}
                                    · {{ rtrim(rtrim(number_format($activity->creditedHours(), 1), '0'), '.') }} h acreditables
                                </p>
                            </div>

                            <div class="mt-auto pt-5">
                                @if ($available !== null)
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="font-medium {{ $available === 0 ? 'text-red-600' : 'text-gray-600' }}">
                                            {{ $available === 0 ? 'Cupo lleno' : ($available === 1 ? 'Queda 1 lugar' : "Quedan {$available} lugares") }}
                                        </span>
                                        <span class="text-gray-400">{{ (int) $activity->registered_count }} / {{ $activity->capacity }}</span>
                                    </div>
                                    <div class="h-1.5 rounded-full bg-upp-surface-high mt-1.5">
                                        <div class="h-1.5 rounded-full {{ $available === 0 ? 'bg-red-500' : 'bg-upp-primary-container' }}" style="width: {{ $percent }}%"></div>
                                    </div>
                                @endif

                                @if ($available === 0)
                                    <span class="mt-4 block text-center px-4 py-2.5 rounded-lg bg-gray-100 text-gray-500 font-semibold cursor-not-allowed">Sin lugares</span>
                                @else
                                    <a href="{{ $enrollUrl }}"
                                       class="mt-4 block text-center px-4 py-2.5 rounded-lg bg-upp-primary-container hover:bg-upp-primary-dark text-white font-semibold transition">
                                        Inscribirme
                                    </a>
                                @endif
                            </div>
                        </div>

                    </article>
                @empty
                    <div class="sm:col-span-2 lg:col-span-3 rounded-2xl bg-white ring-1 ring-upp-surface-high p-10 text-center text-gray-500">
                        @if ($typeId || $day)
                            No hay actividades con esos filtros.
                            <a href="{{ route('home') }}#catalogo" class="font-semibold text-upp-primary-container hover:underline">Ver todas</a>
                        @else
                            Muy pronto publicaremos la programación. ¡Vuelve pronto!
                        @endif
                    </div>
                @endforelse
            </div>

        </section>

    </main>

    <!-- Pie de página -->
    <footer class="bg-upp-primary text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ asset('img/escudo-upp.png') }}" alt="" class="w-10 h-10 rounded-lg bg-white/10 p-1 object-contain">
                <div class="leading-tight">
                    <p class="font-heading font-bold">Aldea Tecnológica UPP 2026</p>
                    <p class="text-xs text-upp-primary-fixed-dim">Universidad Politécnica de Pachuca · 24 al 26 de noviembre</p>
                </div>
            </div>
            <nav class="flex flex-wrap gap-4 text-sm text-white/80">
                <a href="#catalogo" class="hover:text-white">Catálogo</a>
                <a href="#accesos" class="hover:text-white">Accesos</a>
                <a href="{{ route('login') }}" class="hover:text-white">Iniciar sesión</a>
            </nav>
        </div>
    </footer>

</body>
</html>
