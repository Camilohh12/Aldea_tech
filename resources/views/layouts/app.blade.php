<!DOCTYPE html>
<html lang="es-MX" translate="no">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Aldea Tecnológica UPP' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/escudo-upp.png') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Assets Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-upp-surface font-sans text-[#1C1B1F] antialiased">

    <!-- Barra superior: regreso al panel de cada perfil y cierre de sesión -->
    <header class="bg-upp-primary text-white shadow-md">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-2 px-4 sm:gap-3 sm:px-6 lg:px-8">
            <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-2 sm:gap-3">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-white/10 sm:h-10 sm:w-10">
                    <img src="{{ asset('img/escudo-upp.png') }}" alt="Escudo UPP" class="h-7 w-auto sm:h-8">
                </span>
                <span class="min-w-0 leading-tight">
                    <span class="block truncate font-heading text-sm font-bold sm:text-base">Aldea Tecnológica<span class="hidden sm:inline"> UPP</span></span>
                    <span class="hidden text-xs text-upp-primary-fixed-dim sm:block">Universidad Politécnica de Pachuca</span>
                </span>
            </a>

            @auth
                <div class="flex shrink-0 items-center gap-1 sm:gap-1.5">
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center gap-1.5 rounded-lg px-2 py-2 text-sm font-medium sm:px-2.5 text-white/90 transition hover:bg-white/10 hover:text-white">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M11 18l-6-6 6-6" /></svg>
                        Mi panel
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" aria-label="Cerrar sesión"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-2.5 py-2 text-sm font-medium transition hover:bg-white/20">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17l5-5-5-5M20 12H9M12 21H5a1 1 0 01-1-1V4a1 1 0 011-1h7" /></svg>
                            <span class="hidden sm:inline">Cerrar sesión</span>
                        </button>
                    </form>
                </div>
            @endauth
        </div>
    </header>

    <!-- Contenido Dinámico de la Vista -->
    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <footer class="border-t border-upp-surface-high bg-white">
        <div class="mx-auto max-w-7xl px-4 py-5 text-xs text-gray-500 sm:px-6 lg:px-8">
            <span class="font-heading font-bold text-upp-primary">Aldea Tecnológica</span> · © {{ now()->year }} Universidad Politécnica de Pachuca
        </div>
    </footer>
</body>
</html>
