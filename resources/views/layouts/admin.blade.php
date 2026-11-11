<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Administración') - Aldea Tecnológica UPP</title>

    <!-- Fuentes del prototipo: Inter (texto) y Manrope (títulos) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Estilos compilados con Vite (colores institucionales en tailwind.config.js), sin depender de un CDN -->
    @vite(['resources/css/admin.css', 'resources/js/filtros-limpios.js'])

    <style>
        /* Oculta el triángulo por defecto de <summary> en los menús desplegables */
        summary::-webkit-details-marker { display: none; }
        summary { list-style: none; }
    </style>
</head>

<body class="bg-upp-surface font-sans text-[#1C1B1F] antialiased min-h-screen flex flex-col">

@php
    // Menú principal del administrador (igual al prototipo de Figma).
    // Si la ruta todavía no existe, el enlace se muestra deshabilitado.
    $menu = [
        ['label' => 'Panel General', 'route' => 'admin.home',               'active' => 'admin.home'],
        ['label' => 'Talleres',      'route' => 'admin.activities.index',   'active' => 'admin.activities.*'],
        ['label' => 'Asistencias',   'route' => 'admin.attendances.index',  'active' => 'admin.attendances.*'],
        ['label' => 'Certificados',  'route' => 'admin.certificates.index', 'active' => 'admin.certificates.*'],
        ['label' => 'Reportes',      'route' => 'admin.reports.index',      'active' => 'admin.reports.*'],
    ];

    // Submenú "Gestión": infraestructura del evento
    $gestion = [
        ['label' => 'Aulas',        'route' => 'admin.rooms.index',        'active' => 'admin.rooms.*'],
        ['label' => 'Ponentes',     'route' => 'admin.speakers.index',     'active' => 'admin.speakers.*'],
        ['label' => 'Responsables', 'route' => 'admin.responsibles.index', 'active' => 'admin.responsibles.*'],
        ['label' => 'Tutores',      'route' => 'admin.tutors.index',       'active' => 'admin.tutors.*'],
        ['label' => 'Administradores', 'route' => 'admin.administrators.index', 'active' => 'admin.administrators.*'],
    ];

    $gestionActiva = collect($gestion)->contains(fn ($item) => request()->routeIs($item['active']));

    $roles = [
        'super_admin' => 'Super Admin',
        'admin'       => 'Administrador',
    ];
    $rolActual = $roles[auth()->user()?->getRoleNames()->first()] ?? 'Administrador';
@endphp

    <!-- Barra superior -->
    <header class="relative bg-upp-primary text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 lg:h-[72px] gap-6">

                <!-- Logo -->
                <a href="{{ route('admin.home') }}"
                   class="flex items-center gap-3 shrink-0">
                    <span class="w-10 h-10 rounded-lg bg-white flex items-center justify-center text-upp-primary font-heading font-extrabold">
                        UPP
                    </span>
                    <span class="leading-tight">
                        <span class="block font-heading font-bold text-base lg:text-lg">Aldea Tecnológica UPP</span>
                        <span class="block text-xs text-upp-primary-fixed-dim">Universidad Politécnica de Pachuca</span>
                    </span>
                </a>

                <!-- Navegación (escritorio) -->
                <nav class="hidden xl:flex items-stretch self-stretch flex-1 whitespace-nowrap">
                    @foreach ($menu as $item)
                        @if (Route::has($item['route']))
                            <a href="{{ route($item['route']) }}"
                               class="flex items-center px-4 text-sm font-medium transition
                                      {{ request()->routeIs($item['active']) ? 'bg-white/10 text-white' : 'text-white/85 hover:bg-white/5 hover:text-white' }}">
                                {{ $item['label'] }}
                            </a>
                        @else
                            <span class="flex items-center px-4 text-sm font-medium text-white/40 cursor-not-allowed"
                                  title="Próximamente">
                                {{ $item['label'] }}
                            </span>
                        @endif
                    @endforeach

                    <!-- Desplegable de Gestión -->
                    <details class="relative flex" data-dropdown>
                        <summary class="flex items-center gap-1 px-4 text-sm font-medium cursor-pointer transition
                                        {{ $gestionActiva ? 'bg-white/10 text-white' : 'text-white/85 hover:bg-white/5 hover:text-white' }}">
                            Gestión
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>

                        <div class="absolute left-0 top-full mt-1 w-56 bg-white rounded-xl shadow-lg ring-1 ring-upp-surface-high py-2 z-20">
                            @foreach ($gestion as $item)
                                @if (Route::has($item['route']))
                                    <a href="{{ route($item['route']) }}"
                                       class="block px-4 py-2 text-sm
                                              {{ request()->routeIs($item['active']) ? 'bg-upp-surface-low text-upp-primary font-semibold' : 'text-gray-700 hover:bg-upp-surface-low hover:text-upp-primary-dark' }}">
                                        {{ $item['label'] }}
                                    </a>
                                @else
                                    <span class="block px-4 py-2 text-sm text-gray-400 cursor-not-allowed">
                                        {{ $item['label'] }}
                                        <span class="text-xs">· Próximamente</span>
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    </details>
                </nav>

                <!-- Usuario (escritorio) -->
                <details class="relative hidden xl:block shrink-0" data-dropdown>
                    <summary class="flex items-center gap-3 cursor-pointer">
                        <span class="text-right leading-tight max-w-[11rem] truncate">
                            <span class="block text-sm font-semibold">{{ auth()->user()->name }}</span>
                            <span class="block text-xs text-upp-primary-fixed-dim">Rol: {{ $rolActual }}</span>
                        </span>
                        <span class="w-9 h-9 rounded-full bg-upp-primary-container ring-2 ring-upp-on-primary-container flex items-center justify-center text-sm font-bold">
                            {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                        </span>
                    </summary>

                    <div class="absolute right-0 top-full mt-2 w-48 bg-white rounded-xl shadow-lg ring-1 ring-upp-surface-high py-2 z-20">
                        <a href="{{ route('profile.edit') }}"
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-upp-surface-low hover:text-upp-primary-dark">
                            Mi perfil
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                Cerrar sesión
                            </button>
                        </form>
                    </div>
                </details>

                <!-- Botón de menú (celular) -->
                <details class="xl:hidden" data-dropdown>
                    <summary class="p-2 rounded-lg hover:bg-white/10 cursor-pointer" aria-label="Abrir menú">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </summary>

                    <!-- Menú desplegable (celular) -->
                    <div class="absolute left-0 right-0 top-full bg-upp-primary border-t border-white/10 shadow-lg z-20">
                        <nav class="px-4 py-3 space-y-1">
                            @foreach (array_merge($menu, $gestion) as $item)
                                @if (Route::has($item['route']))
                                    <a href="{{ route($item['route']) }}"
                                       class="block px-3 py-2 rounded-lg text-sm font-medium
                                              {{ request()->routeIs($item['active']) ? 'bg-white/10 text-white' : 'text-white/85 hover:bg-white/5' }}">
                                        {{ $item['label'] }}
                                    </a>
                                @else
                                    <span class="block px-3 py-2 text-sm font-medium text-white/40">
                                        {{ $item['label'] }} · Próximamente
                                    </span>
                                @endif
                            @endforeach
                        </nav>

                        <div class="px-4 py-3 border-t border-white/10 flex items-center justify-between">
                            <span class="leading-tight">
                                <span class="block text-sm font-semibold">{{ auth()->user()->name }}</span>
                                <span class="block text-xs text-upp-primary-fixed-dim">Rol: {{ $rolActual }}</span>
                            </span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="text-sm text-upp-primary-fixed-dim hover:text-white">
                                    Cerrar sesión
                                </button>
                            </form>
                        </div>
                    </div>
                </details>

            </div>
        </div>
    </header>

    <!-- Contenido -->
    <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10">

        <!-- Mensajes de éxito y error -->
        @if (session('success'))
            <div class="flex items-start gap-3 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl mb-6">
                <svg class="w-5 h-5 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl mb-6">
                <svg class="w-5 h-5 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @yield('content')

    </main>

    <!-- Pie de página -->
    <footer class="border-t border-upp-surface-high bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 text-xs text-upp-secondary flex flex-col sm:flex-row sm:justify-between gap-1">
            <span class="font-heading font-bold text-upp-primary">Aldea Tecnológica</span>
            <span>© 2026 Universidad Politécnica de Pachuca · 24 al 26 de noviembre</span>
        </div>
    </footer>

    <!-- Cierra los menús desplegables al hacer clic fuera de ellos -->
    <script>
        document.addEventListener('click', (event) => {
            document.querySelectorAll('details[data-dropdown][open]').forEach((menu) => {
                if (!menu.contains(event.target)) {
                    menu.removeAttribute('open');
                }
            });
        });
    </script>

    <!-- Scripts propios de cada vista -->
    @stack('scripts')

</body>
</html>
