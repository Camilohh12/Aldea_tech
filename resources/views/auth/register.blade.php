<x-guest-layout>
    <x-slot name="title">Registro de alumnos</x-slot>
    <x-slot name="width">max-w-2xl</x-slot>

    @php
        $input = 'w-full rounded-xl border-slate-200 py-2.5 pl-10 text-sm placeholder:text-slate-400 focus:border-aldea-500 focus:ring-aldea-500';
        $icono = 'pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-aldea-600';
        $dominio = config('aldea.dominio_correo');
        $digitos = config('aldea.matricula_digitos');
    @endphp

    <div x-data="{ verPass: false, verConf: false, pass: '' }">
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
                        <span class="text-xs text-slate-500">{{ $digitos }} dígitos</span>
                    </div>
                    <div class="relative mt-1.5">
                        <x-icono name="persona" :class="$icono" />
                        <input id="matricula" name="matricula" type="text" inputmode="numeric" autocomplete="off"
                               minlength="{{ $digitos }}" maxlength="{{ $digitos }}" pattern="{{ '[0-9]{'.$digitos.'}' }}"
                               title="La matrícula debe tener {{ $digitos }} dígitos, sin espacios ni letras."
                               value="{{ old('matricula') }}" required autofocus placeholder="Ej. 2023084921" class="{{ $input }}"
                               x-on:input="$el.value = $el.value.replace(/[^0-9]/g, '')">
                    </div>
                    <x-input-error :messages="$errors->get('matricula')" class="mt-1.5" />
                </div>

                {{-- Nombre --}}
                <div>
                    <label for="name" class="text-sm font-semibold">Nombre completo <span class="text-red-600">*</span></label>
                    <div class="relative mt-1.5">
                        <x-icono name="persona" :class="$icono" />
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name"
                               maxlength="{{ \App\Support\ReglasAlumno::NOMBRE_MAX }}" pattern="{{ \App\Support\ReglasAlumno::patronNombreHtml() }}"
                               title="Solo letras, espacios, puntos, apóstrofos y guiones (máximo {{ \App\Support\ReglasAlumno::NOMBRE_MAX }})."
                               placeholder="Ej. Valeria Martínez Solís" class="{{ $input }}">
                    </div>
                    <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
                </div>
            </div>

            {{-- Carrera, cuatrimestre y grupo (de la lista de la coordinación, o «Irregular» / «Otro») --}}
            <div x-data="grupoAlumno(@js((string) old('grupo', '')), @js((string) old('cuatrimestre', '')), @js($tutorPorGrupo))">
            <div class="grid gap-5 sm:grid-cols-[1fr_8rem_10rem]">
                {{-- Carrera --}}
                <div>
                    <label for="carrera" class="text-sm font-semibold">Carrera / Programa <span class="text-red-600">*</span></label>
                    <div class="relative mt-1.5">
                        <x-icono name="birrete" :class="$icono" />
                        <select id="carrera" name="carrera" required x-model="carrera" class="{{ $input }}">
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
                        <select id="cuatrimestre" name="cuatrimestre" required x-model="cuatrimestre" class="{{ $input }}">
                            <option value="" disabled @selected(! old('cuatrimestre'))>Nivel</option>
                            @foreach (config('aldea.cuatrimestres') as $c)
                                <option value="{{ $c }}" @selected((string) old('cuatrimestre') === (string) $c)>{{ is_numeric($c) ? $c.'°' : $c }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-input-error :messages="$errors->get('cuatrimestre')" class="mt-1.5" />
                </div>

                {{-- Grupo: lista con la nomenclatura de la coordinación --}}
                <div>
                    <label for="grupo" class="text-sm font-semibold">Grupo <span class="text-red-600">*</span></label>
                    <div class="relative mt-1.5">
                        <x-icono name="persona" :class="$icono" />
                        <select id="grupo" name="grupo" required :required="grupoObligatorio" x-model="grupo" @change="proponerTutor()" class="{{ $input }} font-mono">
                            <option value="" disabled @selected(! old('grupo'))>Grupo</option>
                            @foreach ($grupos as $opcion)
                                <option value="{{ $opcion }}" @selected(old('grupo') === $opcion)>{{ $opcion }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-input-error :messages="$errors->get('grupo')" class="mt-1.5" />
                </div>
            </div>
            @include('auth.partials.ayuda-grupo')
            </div>

            {{-- Tutor académico (aparece cuando la coordinación ya registró tutores) --}}
            @if ($tutores->isNotEmpty())
                <div>
                    <label for="tutor_id" class="text-sm font-semibold">Tutor académico <span class="text-red-600">*</span></label>
                    <div class="relative mt-1.5">
                        <x-icono name="persona" :class="$icono" />
                        <select id="tutor_id" name="tutor_id" required class="{{ $input }}">
                            <option value="" disabled @selected(! old('tutor_id'))>Selecciona a tu tutor…</option>
                            @foreach ($tutores->groupBy(fn ($tutor) => $tutor->carrera ?: 'Otros tutores')->sortKeys() as $grupo => $lista)
                                <optgroup label="{{ $grupo }}">
                                    @foreach ($lista as $tutor)
                                        <option value="{{ $tutor->id }}" @selected((string) old('tutor_id') === (string) $tutor->id)>{{ $tutor->etiquetaTutor() }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <p class="mt-1.5 text-xs text-slate-500">Quien da seguimiento a tus horas. Si no sabes quién es, pregúntalo en tu coordinación de carrera.</p>
                    <x-input-error :messages="$errors->get('tutor_id')" class="mt-1.5" />
                </div>
            @endif

            {{-- Correo --}}
            <div>
                <div class="flex items-center justify-between">
                    <label for="email" class="text-sm font-semibold">Correo institucional <span class="text-red-600">*</span></label>
                    <span class="rounded-md bg-aldea-50 px-2 py-0.5 text-xs font-medium text-aldea-700">Exclusivo {{ '@'.$dominio }}</span>
                </div>
                <div class="relative mt-1.5">
                    <svg class="{{ $icono }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18v12H3zM3 7l9 6 9-6"/></svg>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"
                           maxlength="255" pattern="{{ \App\Support\ReglasAlumno::patronCorreoHtml() }}"
                           title="Usa tu correo institucional ({{ '@'.$dominio }})."
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
                               minlength="{{ \App\Support\ReglasAlumno::CONTRASENA_MIN }}" pattern="{{ \App\Support\ReglasAlumno::patronContrasenaHtml() }}"
                               title="Mínimo {{ \App\Support\ReglasAlumno::CONTRASENA_MIN }} caracteres con al menos una mayúscula."
                               x-model="pass" placeholder="Mínimo 6, con una mayúscula" class="{{ $input }} pr-11">
                        <button type="button" @click="verPass = !verPass" aria-label="Mostrar u ocultar contraseña"
                                class="absolute right-2 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-lg text-slate-500 hover:bg-aldea-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zM12 15a3 3 0 100-6 3 3 0 000 6z"/></svg>
                        </button>
                    </div>
                    <ul class="mt-1.5 space-y-0.5 text-xs" aria-live="polite">
                        <li :class="pass.length >= {{ \App\Support\ReglasAlumno::CONTRASENA_MIN }} ? 'text-emerald-700' : 'text-slate-500'">
                            <span x-text="pass.length >= {{ \App\Support\ReglasAlumno::CONTRASENA_MIN }} ? '✓' : '•'">•</span> Al menos {{ \App\Support\ReglasAlumno::CONTRASENA_MIN }} caracteres
                        </li>
                        <li :class="/\p{Lu}/u.test(pass) ? 'text-emerald-700' : 'text-slate-500'">
                            <span x-text="/\p{Lu}/u.test(pass) ? '✓' : '•'">•</span> Una letra mayúscula
                        </li>
                    </ul>
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
                    {{-- Las ligas se configuran en .env (ALDEA_URL_REGLAMENTO y ALDEA_URL_AVISO_PRIVACIDAD) --}}
                    <span>
                        Acepto el @include('auth.partials.documento', ['nombre' => 'Reglamento de Participación', 'url' => config('aldea.enlaces.reglamento')]) y el
                        @include('auth.partials.documento', ['nombre' => 'Aviso de Privacidad', 'url' => config('aldea.enlaces.aviso_privacidad')]) de la UPP para la acreditación curricular
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
