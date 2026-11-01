@props(['title' => 'Tutorados'])

@php
    $user = auth()->user();
@endphp

<!DOCTYPE html>
<html lang="es-MX" translate="no">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }} · Aldea Tecnológica UPP</title>
    <link rel="icon" type="image/png" href="{{ asset('img/escudo-upp.png') }}">

    <!-- Inter (texto) y Manrope (títulos), igual que el panel de administración -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-upp-surface font-sans text-[#1C1B1F] antialiased">

    <!-- Barra superior -->
    <header class="bg-upp-primary text-white shadow-md">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:h-[72px] lg:px-8">
            <a href="{{ route('tutor.tutorados') }}" class="flex shrink-0 items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-white/10">
                    <img src="{{ asset('img/escudo-upp.png') }}" alt="Escudo UPP" class="h-8 w-auto">
                </span>
                <span class="leading-tight">
                    <span class="block font-heading text-base font-bold lg:text-lg">Aldea Tecnológica UPP</span>
                    <span class="hidden text-xs text-upp-primary-fixed-dim sm:block">Universidad Politécnica de Pachuca</span>
                </span>
            </a>

            <nav class="hidden flex-1 self-stretch md:flex">
                <a href="{{ route('tutor.tutorados') }}"
                   class="flex items-center px-4 text-sm font-medium transition {{ request()->routeIs('tutor.*') ? 'bg-white/10 text-white' : 'text-white/85 hover:bg-white/5' }}">
                    Gestión de Tutorados
                </a>
            </nav>

            <div class="flex items-center gap-3" x-data="{ open: false }" @click.outside="open = false">
                <div class="hidden text-right leading-tight sm:block">
                    <p class="text-sm font-semibold">{{ $user->name }}</p>
                    <p class="text-xs text-upp-primary-fixed-dim">Tutor Académico</p>
                </div>
                <div class="relative">
                    <button type="button" @click="open = !open" :aria-expanded="open"
                            class="grid h-10 w-10 place-items-center rounded-full bg-upp-secondary-container font-heading text-sm font-bold text-upp-primary">
                        {{ collect(preg_split('/\s+/', trim($user->name)))->reject(fn ($p) => str_ends_with($p, '.'))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') }}
                    </button>
                    <div x-show="open" x-cloak x-transition.origin.top.right
                         class="absolute right-0 z-30 mt-2 w-48 overflow-hidden rounded-xl border border-upp-surface-high bg-white py-1 text-sm text-[#1C1B1F] shadow-lg">
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 hover:bg-upp-surface-low">Mi perfil</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full px-4 py-2 text-left text-red-600 hover:bg-red-50">Cerrar sesión</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        {{ $slot }}
    </main>

    <footer class="border-t border-upp-surface-high bg-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-1 px-4 py-5 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <p><span class="font-heading font-bold text-upp-primary">Aldea Tecnológica</span> · © {{ now()->year }} Universidad Politécnica de Pachuca</p>
            <p>Módulo de tutorías · solo consulta</p>
        </div>
    </footer>
</body>
</html>
