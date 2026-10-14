@props([
    'title' => 'Pase de lista',
    'back' => null,
])

@php
    $user = auth()->user();
    $rol = \Illuminate\Support\Str::ucfirst($user->getRoleNames()->first() ?? 'Personal');
@endphp

<!DOCTYPE html>
<html lang="es-MX" translate="no">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#fbf8ff">

    <title>{{ $title }} · Aldea Tecnológica 2026 · UPP</title>
    <link rel="icon" type="image/png" href="{{ asset('img/escudo-upp.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ $head ?? '' }}
</head>
<body class="min-h-screen bg-lavanda font-sans text-tinta antialiased">
    <header class="sticky top-0 z-30 border-b border-aldea-100/70 bg-lavanda/90 backdrop-blur">
        <div class="mx-auto flex max-w-xl items-center justify-between gap-3 px-4 py-3">
            <div class="flex min-w-0 items-center gap-3">
                @if ($back)
                    <a href="{{ $back }}" class="grid h-9 w-9 shrink-0 place-items-center rounded-xl border border-aldea-100 bg-white text-aldea-800 hover:bg-aldea-50" aria-label="Regresar">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                @endif
                <div class="min-w-0 leading-tight">
                    <p class="flex items-center gap-2 text-lg font-bold tracking-tight">
                        Aldea Tecnológica 2026
                        <span class="rounded-full bg-aldea-100 px-2 py-0.5 text-[11px] font-bold text-aldea-700">UPP</span>
                    </p>
                    <p class="truncate text-[13px] font-medium text-slate-700">{{ $user->name }}</p>
                    <p class="truncate text-xs font-semibold text-aldea-600">{{ $rol }} • Pase de lista</p>
                </div>
            </div>

            <div class="relative shrink-0" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" @click="open = !open" :aria-expanded="open"
                        class="grid h-10 w-10 place-items-center overflow-hidden rounded-xl bg-aldea-900 shadow-sm ring-2 ring-white">
                    <img src="{{ asset('img/escudo-upp.png') }}" alt="Menú de la cuenta" class="h-8 w-auto">
                </button>
                <div x-show="open" x-cloak x-transition.origin.top.right
                     class="absolute right-0 mt-2 w-52 overflow-hidden rounded-xl border border-aldea-100 bg-white py-1 text-sm shadow-lg">
                    <a href="{{ route('pase-lista.index') }}" class="block px-4 py-2 font-medium text-slate-700 hover:bg-aldea-50">Actividades</a>
                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 font-medium text-slate-700 hover:bg-aldea-50">Mi perfil</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full px-4 py-2 text-left font-medium text-red-600 hover:bg-red-50">Cerrar sesión</button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-xl px-4 pb-16 pt-4">
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
                 class="mb-4 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800" role="status">
                <x-icono name="check" class="h-5 w-5 shrink-0" /> {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700" role="alert">
                <x-icono name="prohibido" class="h-5 w-5 shrink-0" /> {{ session('error') }}
            </div>
        @endif

        {{ $slot }}
    </main>
</body>
</html>
