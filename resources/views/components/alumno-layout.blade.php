@props([
    'title' => 'Aldea Tecnológica',
    'section' => null,
    'wide' => false,
])

@php
    $user = auth()->user();
    $agendaCount = $user->enrollments()->where('status', \App\Models\Enrollment::STATUS_REGISTERED)->count();
    $tabs = [
        ['route' => 'alumno.catalogo', 'label' => 'Actividades', 'badge' => null,
         'icon' => 'M8 2v3m8-3v3M3.5 9h17M5 4.5h14A1.5 1.5 0 0120.5 6v13.5A1.5 1.5 0 0119 21H5a1.5 1.5 0 01-1.5-1.5V6A1.5 1.5 0 015 4.5zM7.5 13h9M7.5 16.5h6'],
        ['route' => 'alumno.agenda', 'label' => 'Mi Agenda', 'badge' => $agendaCount ?: null,
         'icon' => 'M12 7v5l3 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['route' => 'alumno.escaner', 'label' => 'Escanear', 'badge' => null,
         'icon' => 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 14h2v2h-2zM14 18h2v2h-2zM18 18h2v2h-2zM6.5 6.5h1v1h-1zM16.5 6.5h1v1h-1zM6.5 16.5h1v1h-1z'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
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

    {{-- Encabezado --}}
    <header class="sticky top-0 z-30 border-b border-aldea-100/70 bg-lavanda/90 backdrop-blur">
        <div @class(['mx-auto flex items-center justify-between gap-3 px-4 py-3', 'max-w-5xl' => $wide, 'max-w-xl' => ! $wide])>
            <div class="min-w-0 leading-tight">
                <p class="flex items-center gap-2 text-lg font-bold tracking-tight">
                    Aldea Tecnológica 2026
                    <span class="rounded-full bg-aldea-100 px-2 py-0.5 text-[11px] font-bold text-aldea-700">UPP</span>
                </p>
                <p class="truncate text-[13px] font-medium text-slate-700">
                    {{ $user->name }}@if ($user->matricula) · {{ $user->matricula }}@endif
                </p>
                <p class="truncate text-xs font-semibold text-aldea-600">
                    {{ $user->carrera ?? 'Alumno' }}@if ($section) • {{ $section }}@endif
                </p>
            </div>

            <div class="relative shrink-0" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" @click="open = !open" :aria-expanded="open"
                        class="grid h-10 w-10 place-items-center overflow-hidden rounded-xl bg-aldea-900 shadow-sm ring-2 ring-white">
                    <img src="{{ asset('img/escudo-upp.png') }}" alt="Menú de la cuenta" class="h-8 w-auto">
                </button>
                <div x-show="open" x-cloak x-transition.origin.top.right
                     class="absolute right-0 mt-2 w-52 overflow-hidden rounded-xl border border-aldea-100 bg-white py-1 text-sm shadow-lg">
                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 font-medium text-slate-700 hover:bg-aldea-50">Mi perfil</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full px-4 py-2 text-left font-medium text-red-600 hover:bg-red-50">Cerrar sesión</button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main @class(['mx-auto px-4 pb-32 pt-4', 'max-w-5xl' => $wide, 'max-w-xl' => ! $wide])>
        {{-- Mensaje de éxito --}}
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
                 class="mb-4 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4" role="status">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </span>
                <p class="pt-1.5 text-sm font-semibold text-emerald-800">{{ session('success') }}</p>
            </div>
        @endif

        {{-- Mensaje de error / conflicto --}}
        @if (session('error'))
            <div class="mb-4 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4" role="alert">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-red-100 text-red-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                        <p class="font-bold text-red-700">{{ session('error_title', 'No se pudo completar la acción') }}</p>
                        <span class="text-xs font-medium text-slate-500">Ahora</span>
                    </div>
                    <p class="mt-0.5 text-sm text-slate-700">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        {{ $slot }}
    </main>

    {{ $floating ?? '' }}

    {{-- Navegación inferior --}}
    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-aldea-100 bg-white/95 backdrop-blur" style="padding-bottom: env(safe-area-inset-bottom)">
        <div class="mx-auto grid max-w-xl grid-cols-3">
            @foreach ($tabs as $tab)
                @php $active = request()->routeIs($tab['route']); @endphp
                <a href="{{ route($tab['route']) }}"
                   @class([
                       'flex flex-col items-center gap-1 pb-3 pt-2.5 text-xs font-semibold transition',
                       'text-aldea-800' => $active,
                       'text-slate-600 hover:text-aldea-700' => ! $active,
                   ])
                   @if ($active) aria-current="page" @endif>
                    <span class="relative">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="{{ $active ? 2 : 1.7 }}" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tab['icon'] }}" />
                        </svg>
                        @if ($tab['badge'])
                            <span class="absolute -right-2 -top-1.5 grid h-4 min-w-4 place-items-center rounded-full bg-aldea-800 px-1 text-[10px] font-bold text-white">{{ $tab['badge'] }}</span>
                        @endif
                    </span>
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </div>
    </nav>

    {{ $scripts ?? '' }}
</body>
</html>
