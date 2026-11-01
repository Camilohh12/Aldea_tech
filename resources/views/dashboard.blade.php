{{-- Página de inicio para cuentas sin sección propia (p. ej. ponentes o cuentas sin rol).
     Admin, alumno, tutor, responsable y docente se redirigen a su panel desde la ruta /dashboard. --}}
<x-guest-layout>
    <x-slot name="title">Mi cuenta</x-slot>

    @php
        $user = auth()->user();
        $esPonente = $user->hasRole('ponente');
        $nombre = collect(preg_split('/\s+/', trim($user->name)))->reject(fn ($p) => str_ends_with($p, '.'))->first() ?? $user->name;
    @endphp

    <div class="text-center">
        <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-aldea-900 shadow-lg shadow-aldea-900/20 ring-4 ring-aldea-100">
            <img src="{{ asset('img/escudo-upp.png') }}" alt="Escudo UPP" class="h-12 w-auto">
        </span>
        <h1 class="mt-5 text-2xl font-extrabold tracking-tight">Hola, {{ $nombre }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $user->email }}</p>
    </div>

    <x-auth-session-status class="mt-5" :status="session('success')" />

    <div class="mt-6 rounded-2xl bg-aldea-50 px-5 py-4 text-sm text-slate-700">
        @if ($esPonente)
            <p class="font-semibold text-aldea-800">Tu cuenta de ponente está activa.</p>
            <p class="mt-1">
                La coordinación registra tus actividades y emite tu certificado de participación cuando termina cada una.
                No necesitas hacer nada más aquí; si algo de tu información no es correcto, comunícate con la coordinación del evento.
            </p>
        @else
            <p class="font-semibold text-aldea-800">Tu cuenta todavía no tiene una sección asignada.</p>
            <p class="mt-1">
                Si eres tutor, docente o responsable de una actividad, pide a la coordinación de Aldea Tecnológica que active tu acceso.
                Los alumnos se registran desde «Crear mi cuenta» con su matrícula.
            </p>
        @endif
    </div>

    <div class="mt-6 grid gap-3 sm:grid-cols-2">
        <a href="{{ route('home') }}#catalogo"
           class="flex items-center justify-center gap-2 rounded-xl bg-aldea-800 px-4 py-3 text-center text-sm font-semibold text-white shadow-lg shadow-aldea-900/20 transition hover:bg-aldea-700">
            Ver actividades
        </a>
        <a href="{{ route('profile.edit') }}"
           class="flex items-center justify-center rounded-xl border border-aldea-100 bg-white px-4 py-3 text-center text-sm font-semibold text-aldea-800 transition hover:bg-aldea-50">
            Mi perfil
        </a>
    </div>

    <form method="POST" action="{{ route('logout') }}" class="mt-5 border-t border-slate-100 pt-5 text-center">
        @csrf
        <button type="submit" class="text-sm font-semibold text-red-600 hover:underline">Cerrar sesión</button>
    </form>
</x-guest-layout>
