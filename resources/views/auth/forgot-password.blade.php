<x-guest-layout>
    <x-slot name="title">Recuperar contraseña</x-slot>

    @php
        $icono = 'pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-aldea-600';
    @endphp

    {{-- Encabezado --}}
    <div class="text-center">
        <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-aldea-900 shadow-lg shadow-aldea-900/20 ring-4 ring-aldea-100">
            <img src="{{ asset('img/escudo-upp.png') }}" alt="Escudo UPP" class="h-12 w-auto">
        </span>
        <h1 class="mt-5 text-2xl font-extrabold tracking-tight">Recuperar contraseña</h1>
        <p class="mx-auto mt-2 max-w-sm text-sm text-slate-600">
            Escribe el correo con el que te registraste y te enviaremos un enlace para elegir una contraseña nueva.
        </p>
    </div>

    <x-auth-session-status class="mt-5" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
        @csrf

        {{-- Correo --}}
        <div>
            <label for="email" class="text-sm font-semibold">Correo</label>
            <div class="relative mt-1.5">
                <svg class="{{ $icono }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18v12H3zM3 7l9 6 9-6"/></svg>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                       placeholder="nombre.apellido{{ '@'.config('aldea.dominio_correo') }}"
                       class="w-full rounded-xl border-slate-200 py-2.5 pl-10 text-sm placeholder:text-slate-400 focus:border-aldea-500 focus:ring-aldea-500">
            </div>
            <p class="mt-1 text-xs text-slate-500">Los alumnos usan su correo institucional, no la matrícula.</p>
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <button type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-aldea-800 py-3 text-[15px] font-semibold text-white shadow-lg shadow-aldea-900/20 transition hover:bg-aldea-700">
            Enviar enlace
        </button>
    </form>

    <div class="mt-6 border-t border-slate-100 pt-5 text-center text-sm text-slate-600">
        ¿Ya la recordaste?
        <a href="{{ route('login') }}" class="font-semibold text-aldea-700 hover:underline">Iniciar sesión</a>
    </div>
</x-guest-layout>
