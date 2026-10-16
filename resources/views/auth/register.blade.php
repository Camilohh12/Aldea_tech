<x-guest-layout>
    <x-slot name="title">Registro de alumnos</x-slot>
    <x-slot name="width">max-w-2xl</x-slot>

    @php
        $input = 'w-full rounded-xl border-slate-200 py-2.5 pl-10 text-sm placeholder:text-slate-400 focus:border-aldea-500 focus:ring-aldea-500';
        $icono = 'pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-aldea-600';
        $dominio = config('aldea.dominio_correo');
    @endphp

    <div x-data="{ verPass: false, verConf: false }">
        {{-- Encabezado --}}
        <div class="text-center">
            <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-aldea-900 shadow-lg shadow-aldea-900/20 ring-4 ring-aldea-100">
                <img src="{{ asset('img/escudo-upp.png') }}" alt="Escudo UPP" class="h-12 w-auto">
            </span>
            <p class="mt-4 inline-block rounded-full bg-aldea-50 px-3 py-1 text-xs font-semibold text-aldea-700">Portal de Acreditación Académica</p>
            <h1 class="mt-2 text-2xl font-extrabold tracking-tight">Registro de alumnos</h1>
            <p class="mt-1 text-sm text-slate-500">Aldea Tecnológica UPP</p>
            <p class="mx-auto mt-3 max-w-md text-sm text-slate-600">
                Completa tus datos para inscribirte a talleres y conferencias, obtener tu pase de acceso y acreditar tus horas.
            </p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="mt-7 space-y-5">
            @csrf

            <div class="grid gap-5 sm:grid-cols-2">
                {{-- Matrícula --}}
                <div>
                    <div class="flex items-center justify-between">
                        <label for="matricula" class="text-sm font-semibold">Matrícula <span class="text-red-600">*</span></label>
                        <span class="text-xs text-slate-500">9 dígitos</span>
                    </div>
                    <div class="relative mt-1.5">
                        <x-icono name="persona" :class="$icono" />
                        <input id="matricula" name="matricula" type="text" inputmode="numeric" maxlength="9" pattern="\d{9}"
                               value="{{ old('matricula') }}" required autofocus placeholder="Ej. 202308492" class="{{ $input }}">
                    </div>
                    <x-input-error :messages="$errors->get('matricula')" class="mt-1.5" />
                </div>

                {{-- Nombre --}}
                <div>
                    <label for="name" class="text-sm font-semibold">Nombre completo <span class="text-red-600">*</span></label>
                    <div class="relative mt-1.5">
                        <x-icono name="persona" :class="$icono" />
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name"
                               placeholder="Ej. Valeria Martínez Solís" class="{{ $input }}">
                    </div>
                    <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-[1fr_10rem]">
                {{-- Carrera --}}
                <div>
                    <label for="carrera" class="text-sm font-semibold">Carrera / Programa <span class="text-red-600">*</span></label>
                    <div class="relative mt-1.5">
                        <x-icono name="birrete" :class="$icono" />
                        <select id="carrera" name="carrera" required class="{{ $input }}">
                            <option value="" disabled @selected(! old('carrera'))>Selecciona tu carrera…</option>
                            @foreach (config('aldea.carreras') as $carrera)
                                <option value="{{ $carrera }}" @selected(old('carrera') === $carrera)>{{ $carrera }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-input-error :messages="$errors->get('carrera')" class="mt-1.5" />
                </div>

                {{-- Cuatrimestre --}}
                <div>
                    <label for="cuatrimestre" class="text-sm font-semibold">Cuatrimestre <span class="text-red-600">*</span></label>
                    <div class="relative mt-1.5">
                        <x-icono name="calendario" :class="$icono" />
                        <select id="cuatrimestre" name="cuatrimestre" required class="{{ $input }}">
                            <option value="" disabled @selected(! old('cuatrimestre'))>Nivel</option>
                            @foreach (config('aldea.cuatrimestres') as $c)
                                <option value="{{ $c }}" @selected((string) old('cuatrimestre') === (string) $c)>{{ is_numeric($c) ? $c.'°' : $c }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-input-error :messages="$errors->get('cuatrimestre')" class="mt-1.5" />
                </div>
            </div>

            {{-- Correo --}}
            <div>
                <div class="flex items-center justify-between">
                    <label for="email" class="text-sm font-semibold">Correo institucional <span class="text-red-600">*</span></label>
                    <span class="rounded-md bg-aldea-50 px-2 py-0.5 text-xs font-medium text-aldea-700">Exclusivo {{ '@'.$dominio }}</span>
                </div>
                <div class="relative mt-1.5">
                    <svg class="{{ $icono }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18v12H3zM3 7l9 6 9-6"/></svg>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"
                           placeholder="nombre.apellido{{ '@'.$dominio }}" class="{{ $input }}">
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                {{-- Contraseña --}}
                <div>
                    <label for="password" class="text-sm font-semibold">Contraseña <span class="text-red-600">*</span></label>
                    <div class="relative mt-1.5">
                        <x-icono name="candado" :class="$icono" />
                        <input id="password" name="password" :type="verPass ? 'text' : 'password'" type="password" required autocomplete="new-password"
                               placeholder="Mínimo 8 caracteres" class="{{ $input }} pr-11">
                        <button type="button" @click="verPass = !verPass" aria-label="Mostrar u ocultar contraseña"
                                class="absolute right-2 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-lg text-slate-500 hover:bg-aldea-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zM12 15a3 3 0 100-6 3 3 0 000 6z"/></svg>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
                </div>

                {{-- Confirmación --}}
                <div>
                    <label for="password_confirmation" class="text-sm font-semibold">Confirmar contraseña <span class="text-red-600">*</span></label>
                    <div class="relative mt-1.5">
                        <x-icono name="sincronizar" :class="$icono" />
                        <input id="password_confirmation" name="password_confirmation" :type="verConf ? 'text' : 'password'" type="password" required autocomplete="new-password"
                               placeholder="Repite tu contraseña" class="{{ $input }} pr-11">
                        <button type="button" @click="verConf = !verConf" aria-label="Mostrar u ocultar contraseña"
                                class="absolute right-2 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-lg text-slate-500 hover:bg-aldea-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zM12 15a3 3 0 100-6 3 3 0 000 6z"/></svg>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
                </div>
            </div>

            {{-- Aceptación --}}
            <div>
                <label class="flex items-start gap-2.5 text-sm text-slate-600">
                    <input type="checkbox" name="acepto" value="1" @checked(old('acepto')) required
                           class="mt-0.5 rounded border-slate-300 text-aldea-700 focus:ring-aldea-500">
                    <span>
                        Acepto el <strong class="text-aldea-800">Reglamento de Participación</strong> y el
                        <strong class="text-aldea-800">Aviso de Privacidad</strong> de la UPP para la acreditación curricular
                        y el control de asistencia en Aldea Tecnológica.
                    </span>
                </label>
                <x-input-error :messages="$errors->get('acepto')" class="mt-1.5" />
            </div>

            <button type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-aldea-800 py-3 text-[15px] font-semibold text-white shadow-lg shadow-aldea-900/20 transition hover:bg-aldea-700">
                Crear mi cuenta
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>
            </button>
        </form>

        <p class="mt-6 border-t border-slate-100 pt-5 text-center text-sm text-slate-600">
            ¿Ya tienes una cuenta?
            <a href="{{ route('login') }}" class="font-semibold text-aldea-700 hover:underline">Iniciar sesión</a>
        </p>
    </div>
</x-guest-layout>
