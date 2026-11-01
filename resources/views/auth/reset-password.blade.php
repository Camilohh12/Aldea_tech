<x-guest-layout>
    <x-slot name="title">Nueva contraseña</x-slot>

    @php
        $input = 'w-full rounded-xl border-slate-200 py-2.5 pl-10 pr-11 text-sm placeholder:text-slate-400 focus:border-aldea-500 focus:ring-aldea-500';
        $icono = 'pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-aldea-600';
        $ojo = 'absolute right-2 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-lg text-slate-500 hover:bg-aldea-50';
    @endphp

    <div x-data="{ verPass: false, verConf: false }">
        {{-- Encabezado --}}
        <div class="text-center">
            <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-aldea-900 shadow-lg shadow-aldea-900/20 ring-4 ring-aldea-100">
                <img src="{{ asset('img/escudo-upp.png') }}" alt="Escudo UPP" class="h-12 w-auto">
            </span>
            <h1 class="mt-5 text-2xl font-extrabold tracking-tight">Elige una contraseña nueva</h1>
            <p class="mt-1 text-sm text-slate-500">Aldea Tecnológica UPP</p>
        </div>

        <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-5">
            @csrf

            {{-- Código del enlace que llegó por correo --}}
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            {{-- Correo --}}
            <div>
                <label for="email" class="text-sm font-semibold">Correo</label>
                <div class="relative mt-1.5">
                    <svg class="{{ $icono }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18v12H3zM3 7l9 6 9-6"/></svg>
                    <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required autocomplete="username"
                           class="w-full rounded-xl border-slate-200 py-2.5 pl-10 text-sm focus:border-aldea-500 focus:ring-aldea-500">
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
            </div>

            {{-- Contraseña nueva --}}
            <div>
                <label for="password" class="text-sm font-semibold">Contraseña nueva</label>
                <div class="relative mt-1.5">
                    <x-icono name="candado" :class="$icono" />
                    <input id="password" name="password" :type="verPass ? 'text' : 'password'" type="password" required autofocus autocomplete="new-password"
                           placeholder="Mínimo 8 caracteres" class="{{ $input }}">
                    <button type="button" @click="verPass = !verPass" :aria-label="verPass ? 'Ocultar contraseña' : 'Mostrar contraseña'" class="{{ $ojo }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zM12 15a3 3 0 100-6 3 3 0 000 6z" /></svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
            </div>

            {{-- Confirmación --}}
            <div>
                <label for="password_confirmation" class="text-sm font-semibold">Confirmar contraseña</label>
                <div class="relative mt-1.5">
                    <x-icono name="sincronizar" :class="$icono" />
                    <input id="password_confirmation" name="password_confirmation" :type="verConf ? 'text' : 'password'" type="password" required autocomplete="new-password"
                           placeholder="Repite tu contraseña" class="{{ $input }}">
                    <button type="button" @click="verConf = !verConf" :aria-label="verConf ? 'Ocultar contraseña' : 'Mostrar contraseña'" class="{{ $ojo }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zM12 15a3 3 0 100-6 3 3 0 000 6z" /></svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
            </div>

            <button type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-aldea-800 py-3 text-[15px] font-semibold text-white shadow-lg shadow-aldea-900/20 transition hover:bg-aldea-700">
                Guardar contraseña
            </button>
        </form>
    </div>
</x-guest-layout>
