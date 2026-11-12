@php
    $icono = 'pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-aldea-600';
    $input = 'w-full rounded-xl border-slate-200 py-2.5 pl-10 text-sm focus:border-aldea-500 focus:ring-aldea-500';
    $esAlumno = $user->hasRole('alumno');
    $dominio = config('aldea.dominio_correo');
@endphp

<section>
    <header>
        <h2 class="font-heading text-lg font-bold text-upp-primary">Datos de tu cuenta</h2>
        <p class="mt-1 text-sm text-slate-500">
            {{ $esAlumno ? 'Tu nombre, tu carrera, tu cuatrimestre, tu grupo y el correo con el que inicias sesión.' : 'Tu nombre y el correo con el que inicias sesión.' }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        <div>
            <label for="name" class="text-sm font-semibold">Nombre completo</label>
            <div class="relative mt-1.5">
                <x-icono name="persona" :class="$icono" />
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="name" maxlength="{{ $esAlumno ? \App\Support\ReglasAlumno::NOMBRE_MAX : 255 }}"
                       @if ($esAlumno) pattern="{{ \App\Support\ReglasAlumno::patronNombreHtml() }}" title="Solo letras, espacios, puntos, apóstrofos y guiones." @endif
                       class="{{ $input }}">
            </div>
            <x-input-error class="mt-1.5" :messages="$errors->get('name')" />
        </div>

        {{-- Datos académicos: solo los alumnos, con las mismas opciones del registro --}}
        @if ($esAlumno)
            @php $grupoActual = (string) old('grupo', $user->grupo); @endphp
            <div x-data="grupoAlumno(@js($grupoActual), @js((string) old('cuatrimestre', $user->cuatrimestre ?? '')), {})">
            <div class="grid gap-5 sm:grid-cols-[1fr_8rem_10rem]">
                <div>
                    <label for="carrera" class="text-sm font-semibold">Carrera / Programa</label>
                    <div class="relative mt-1.5">
                        <x-icono name="birrete" :class="$icono" />
                        <select id="carrera" name="carrera" required x-model="carrera" class="{{ $input }}">
                            <option value="" disabled @selected(! old('carrera', $user->carrera))>Selecciona tu carrera…</option>
                            @foreach (config('aldea.carreras') as $carrera)
                                <option value="{{ $carrera }}" @selected(old('carrera', $user->carrera) === $carrera)>{{ $carrera }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-input-error class="mt-1.5" :messages="$errors->get('carrera')" />
                </div>

                <div>
                    <label for="cuatrimestre" class="text-sm font-semibold">Cuatrimestre</label>
                    <div class="relative mt-1.5">
                        <x-icono name="calendario" :class="$icono" />
                        <select id="cuatrimestre" name="cuatrimestre" required x-model="cuatrimestre" class="{{ $input }}">
                            <option value="" disabled @selected(! old('cuatrimestre', $user->cuatrimestre))>Nivel</option>
                            @foreach (config('aldea.cuatrimestres') as $c)
                                <option value="{{ $c }}" @selected((string) old('cuatrimestre', $user->cuatrimestre) === (string) $c)>{{ is_numeric($c) ? $c.'°' : $c }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-input-error class="mt-1.5" :messages="$errors->get('cuatrimestre')" />
                </div>

                <div>
                    <label for="grupo" class="text-sm font-semibold">Grupo</label>
                    <div class="relative mt-1.5">
                        <x-icono name="persona" :class="$icono" />
                        <select id="grupo" name="grupo" required :required="grupoObligatorio" x-model="grupo" class="{{ $input }} font-mono">
                            <option value="" disabled @selected($grupoActual === '')>Grupo</option>
                            @foreach (\App\Support\Grupo::opciones() as $opcion)
                                <option value="{{ $opcion }}" @selected($grupoActual === $opcion)>{{ $opcion }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-input-error class="mt-1.5" :messages="$errors->get('grupo')" />
                </div>
            </div>
            @include('auth.partials.ayuda-grupo')
            </div>
        @endif

        <div>
            <label for="email" class="text-sm font-semibold">Correo</label>
            <div class="relative mt-1.5">
                <svg class="{{ $icono }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18v12H3zM3 7l9 6 9-6"/></svg>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username" maxlength="255"
                       @if ($esAlumno) pattern="{{ \App\Support\ReglasAlumno::patronCorreoHtml() }}" title="Usa tu correo institucional ({{ '@'.$dominio }})." @endif
                       class="{{ $input }}">
            </div>
            <x-input-error class="mt-1.5" :messages="$errors->get('email')" />
            <p class="mt-1.5 text-xs text-slate-500">Si lo cambias, tendrás que verificar el correo nuevo.</p>

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-3 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Tu correo todavía no está verificado.
                    <button form="send-verification" class="font-semibold underline hover:text-amber-900">
                        Reenviar el enlace de verificación
                    </button>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-emerald-700">Te enviamos un enlace nuevo a tu correo.</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <button type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-aldea-800 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-aldea-700">
                Guardar cambios
            </button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                   class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700">
                    <x-icono name="check" class="h-4 w-4" /> Datos guardados.
                </p>
            @endif
        </div>
    </form>
</section>
