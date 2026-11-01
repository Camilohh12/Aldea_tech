<x-guest-layout>
    <x-slot name="title">Iniciar sesión</x-slot>

    @php
        // Textos del campo de usuario según el perfil elegido
        $perfiles = [
            'alumno' => ['label' => 'Alumno', 'campo' => 'Correo o matrícula', 'placeholder' => 'Ej. 202308492 o usuario@upp.edu.mx',
                'ayuda' => 'Ingresa tu matrícula de 9 dígitos o tu correo institucional.',
                'icon' => 'M12 4L2 9l10 5 10-5-10-5zM6 11v5c3 2 9 2 12 0v-5'],
            'docente' => ['label' => 'Docente', 'campo' => 'Correo institucional', 'placeholder' => 'usuario@upp.edu.mx',
                'ayuda' => 'Tutores, docentes y ponentes entran con su correo.',
                'icon' => 'M4 7h16v12H4zM9 7V5h6v2'],
            'admin' => ['label' => 'Administrador', 'campo' => 'Correo institucional', 'placeholder' => 'admin@upp.edu.mx',
                'ayuda' => 'Acceso para la coordinación del evento.',
                'icon' => 'M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3z'],
        ];
        // Perfil preseleccionado: el del intento anterior, el que llega desde la portada (?perfil=) o alumno
        $perfilInicial = old('perfil', in_array(request('perfil'), array_keys($perfiles), true) ? request('perfil') : 'alumno');
    @endphp

    <div x-data="{ perfil: @js($perfilInicial), verPass: false, perfiles: @js($perfiles) }">
        {{-- Encabezado --}}
        <div class="text-center">
            <div class="relative mx-auto h-16 w-16">
                <span class="grid h-16 w-16 place-items-center rounded-2xl bg-aldea-900 shadow-lg shadow-aldea-900/20 ring-4 ring-aldea-100">
                    <img src="{{ asset('img/escudo-upp.png') }}" alt="Escudo UPP" class="h-12 w-auto">
                </span>
                <span class="absolute -bottom-1.5 -right-2 rounded-full bg-aldea-800 px-1.5 py-0.5 text-[10px] font-bold text-white ring-2 ring-white">UPP</span>
            </div>
            <h1 class="mt-5 text-2xl font-extrabold tracking-tight">Aldea Tecnológica UPP</h1>
            <p class="mt-1 text-sm text-slate-500">Iniciar sesión</p>
        </div>

        <x-auth-session-status class="mt-5" :status="session('status')" />

        {{-- Selector de perfil --}}
        <p class="mt-6 text-center text-xs font-bold uppercase tracking-[0.14em] text-slate-600">Selecciona tu perfil de acceso</p>
        <div class="mt-2 grid grid-cols-3 gap-1 rounded-xl border border-aldea-100 bg-aldea-50/60 p-1" role="tablist">
            @foreach ($perfiles as $clave => $p)
                <button type="button" role="tab" @click="perfil = '{{ $clave }}'" :aria-selected="perfil === '{{ $clave }}'"
                        :class="perfil === '{{ $clave }}' ? 'bg-aldea-800 text-white shadow-sm' : 'text-slate-600 hover:bg-white'"
                        class="flex items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-xs font-semibold transition sm:text-sm">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $p['icon'] }}" />
                    </svg>
                    {{ $p['label'] }}
                </button>
            @endforeach
        </div>

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="perfil" :value="perfil">

            {{-- Usuario --}}
            <div>
                <label for="email" class="text-sm font-semibold" x-text="perfiles[perfil].campo">Correo o matrícula</label>
                <div class="relative mt-1.5">
                    <x-icono name="persona" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-aldea-600" />
                    <input id="email" name="email" type="text" value="{{ old('email') }}" required autofocus autocomplete="username"
                           :placeholder="perfiles[perfil].placeholder"
                           class="w-full rounded-xl border-slate-200 py-2.5 pl-10 text-sm placeholder:text-slate-400 focus:border-aldea-500 focus:ring-aldea-500">
                </div>
                <p class="mt-1 text-xs text-slate-500" x-text="perfiles[perfil].ayuda"></p>
                <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
            </div>

            {{-- Contraseña --}}
            <div>
                <div class="flex items-center justify-between">
                    <label for="password" class="text-sm font-semibold">Contraseña</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-xs font-semibold text-aldea-700 hover:underline">¿Olvidaste tu contraseña?</a>
                    @endif
                </div>
                <div class="relative mt-1.5">
                    <x-icono name="candado" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-aldea-600" />
                    <input id="password" name="password" :type="verPass ? 'text' : 'password'" type="password" required autocomplete="current-password"
                           placeholder="••••••••••••"
                           class="w-full rounded-xl border-slate-200 py-2.5 pl-10 pr-11 text-sm placeholder:text-slate-400 focus:border-aldea-500 focus:ring-aldea-500">
                    <button type="button" @click="verPass = !verPass" :aria-label="verPass ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                            class="absolute right-2 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-lg text-slate-500 hover:bg-aldea-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path x-show="!verPass" stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zM12 15a3 3 0 100-6 3 3 0 000 6z" />
                            <path x-show="verPass" x-cloak stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.6 10.6a2 2 0 002.8 2.8M9.9 5.1A10 10 0 0112 5c6.5 0 10 7 10 7a17 17 0 01-3.2 4.2M6.6 6.6C3.9 8.4 2 12 2 12s3.5 7 10 7c1.7 0 3.2-.5 4.5-1.2" />
                        </svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" @checked(old('remember', true)) class="rounded border-slate-300 text-aldea-700 focus:ring-aldea-500">
                Recordar mi sesión
            </label>

            <button type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-aldea-800 py-3 text-[15px] font-semibold text-white shadow-lg shadow-aldea-900/20 transition hover:bg-aldea-700">
                Iniciar sesión
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg>
            </button>
        </form>

        <div class="mt-6 border-t border-slate-100 pt-5 text-center text-sm">
            <p x-show="perfil === 'alumno'" class="text-slate-600">
                ¿Aún no tienes cuenta?
                <a href="{{ route('register') }}" class="font-semibold text-aldea-700 hover:underline">Regístrate como alumno</a>
            </p>
            <p x-show="perfil !== 'alumno'" x-cloak class="text-slate-500">Las cuentas de docentes y administradores las crea la coordinación del evento.</p>
        </div>
    </div>
</x-guest-layout>
