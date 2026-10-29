<!DOCTYPE html>
<html lang="es-MX" translate="no">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#fbf8ff">

        <title>{{ $title ?? 'Acceso' }} · Aldea Tecnológica UPP</title>
        <link rel="icon" type="image/png" href="{{ asset('img/escudo-upp.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen flex-col bg-lavanda font-heading text-tinta antialiased">
        {{-- Fondo con brillos suaves --}}
        <div aria-hidden="true" class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
            <div class="absolute -left-32 -top-32 h-96 w-96 rounded-full bg-aldea-100/70 blur-3xl"></div>
            <div class="absolute -bottom-40 -right-24 h-[28rem] w-[28rem] rounded-full bg-aldea-200/40 blur-3xl"></div>
        </div>

        <header class="border-b border-aldea-100/80 bg-white/70 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 text-sm">
                <a href="{{ url('/') }}" class="flex items-center gap-2 font-medium text-slate-600 hover:text-aldea-800">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5m6-6l-6 6 6 6"/></svg>
                    Volver al portal
                </a>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-aldea-100 bg-white px-3 py-1 text-xs font-medium text-slate-600">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Aldea Tecnológica 2026
                </span>
            </div>
        </header>

        <main class="flex flex-1 flex-col items-center justify-center px-4 py-10">
            <div class="w-full {{ $width ?? 'max-w-md' }}">
                <div class="rounded-3xl border border-aldea-100/80 bg-white px-6 py-8 shadow-xl shadow-aldea-900/5 sm:px-8">
                    {{ $slot }}
                </div>

                <p class="mt-5 text-center text-xs text-slate-500">
                    <span class="font-semibold text-slate-700">Universidad Politécnica de Pachuca</span><br>
                    Sistema de gestión de Aldea Tecnológica
                </p>
            </div>
        </main>

        <footer class="border-t border-aldea-100/80 bg-white/70">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 text-xs text-slate-500">
                <p><span class="font-bold text-tinta">Aldea Tecnológica UPP</span> · Noviembre 2026</p>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="hover:text-aldea-800">Recuperar contraseña</a>
                @endif
            </div>
        </footer>
    </body>
</html>
