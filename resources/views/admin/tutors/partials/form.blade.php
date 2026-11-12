{{-- Campos del tutor, compartidos por create y edit.
     Recibe $tutor (null al registrar). --}}
@php
    $field = 'w-full rounded-lg border px-4 py-3 bg-white focus:outline-none focus:ring-2 focus:ring-upp-primary-container';
    $border = fn ($name) => $errors->has($name) ? 'border-red-400' : 'border-gray-300';
@endphp

<!-- Grupos -->
<div class="mb-6">
    <label for="grupos_tutoria" class="block text-sm font-semibold text-upp-primary mb-2">
        Grupos que atiende <span class="font-normal text-gray-500">(opcional)</span>
    </label>
    <input type="text" id="grupos_tutoria" name="grupos_tutoria" maxlength="160" autocomplete="off"
           value="{{ old('grupos_tutoria', $tutor?->grupos_tutoria) }}"
           placeholder="Ej. TIID_01_02, TIID_04_01"
           pattern="\s*[A-Za-z]{2,6}_\d{1,2}_\d{1,2}(\s*,\s*[A-Za-z]{2,6}_\d{1,2}_\d{1,2})*\s*,?\s*"
           title="Escribe los grupos como TIID_01_02; si son varios, sepáralos con comas."
           class="{{ $field }} {{ $border('grupos_tutoria') }} font-mono uppercase">
    <p class="text-xs mt-2 text-gray-500">Aparecen al principio del nombre del tutor en la lista y en el registro de alumnos.</p>
    @error('grupos_tutoria')
        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
    @enderror
</div>

<!-- Nombre -->
<div class="mb-6">
    <label for="name" class="block text-sm font-semibold text-upp-primary mb-2">
        Nombre completo <span class="text-red-600">*</span>
    </label>
    <input type="text" id="name" name="name" maxlength="255" required autocomplete="off"
           value="{{ old('name', $tutor?->name) }}"
           placeholder="Ej. Mtra. Laura Gómez Hernández"
           class="{{ $field }} {{ $border('name') }}">
    <p class="text-xs mt-2 text-gray-500">Así lo verán los alumnos al elegir a su tutor en el registro.</p>
    @error('name')
        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
    @enderror
</div>

<!-- Correo -->
<div class="mb-6">
    <label for="email" class="block text-sm font-semibold text-upp-primary mb-2">
        Correo electrónico <span class="text-red-600">*</span>
    </label>
    <input type="email" id="email" name="email" maxlength="255" required autocomplete="off"
           value="{{ old('email', $tutor?->email) }}"
           placeholder="Ej. lgomez@upp.edu.mx"
           class="{{ $field }} {{ $border('email') }}">
    @if ($tutor?->correoPendiente() && ! $errors->has('email'))
        <p class="text-xs mt-2 text-amber-700">Correo provisional: escribe su correo real y genera una contraseña para darle acceso.</p>
    @else
        <p class="text-xs mt-2 text-gray-500">Con este correo iniciará sesión.</p>
    @endif
    @error('email')
        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
    @enderror
</div>

<!-- Carrera -->
<div class="mb-6">
    <label for="carrera" class="block text-sm font-semibold text-upp-primary mb-2">
        Carrera que atiende <span class="font-normal text-gray-500">(opcional)</span>
    </label>
    <select id="carrera" name="carrera" class="{{ $field }} {{ $border('carrera') }}">
        <option value="">Sin carrera específica</option>
        @foreach (config('aldea.carreras') as $carrera)
            <option value="{{ $carrera }}" @selected(old('carrera', $tutor?->carrera) === $carrera)>{{ $carrera }}</option>
        @endforeach
    </select>
    <p class="text-xs mt-2 text-gray-500">En el registro, los tutores aparecen agrupados por carrera para que el alumno encuentre al suyo.</p>
    @error('carrera')
        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
    @enderror
</div>

<!-- Contraseña -->
<div class="mb-8 rounded-xl bg-upp-surface-low p-5">

    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
        <p class="text-sm font-semibold text-upp-primary">
            @if ($tutor)
                Cambiar contraseña <span class="font-normal text-gray-500">(opcional)</span>
            @else
                Contraseña temporal <span class="text-red-600">*</span>
            @endif
        </p>

        <div class="flex gap-2">
            <button type="button" id="generate-password"
                    class="px-3 py-1.5 rounded-lg bg-white border border-upp-surface-high text-sm text-upp-primary-container font-medium hover:bg-upp-surface-high transition">
                Generar
            </button>
            <button type="button" id="toggle-password"
                    class="px-3 py-1.5 rounded-lg bg-white border border-upp-surface-high text-sm text-gray-700 hover:bg-upp-surface-high transition">
                Mostrar
            </button>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="password" class="block text-xs font-semibold text-gray-600 mb-1">Contraseña</label>
            <input type="password" id="password" name="password" minlength="8" autocomplete="new-password"
                   @required(! $tutor)
                   class="{{ $field }} {{ $border('password') }}">
        </div>
        <div>
            <label for="password_confirmation" class="block text-xs font-semibold text-gray-600 mb-1">Confirmar contraseña</label>
            <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" autocomplete="new-password"
                   @required(! $tutor)
                   class="{{ $field }} {{ $border('password') }}">
        </div>
    </div>

    <p class="text-xs mt-3 text-gray-500">
        @if ($tutor)
            Déjala vacía para conservar la contraseña actual.
        @else
            Mínimo 8 caracteres. Compártela con el tutor; podrá cambiarla desde su perfil.
        @endif
    </p>

    @error('password')
        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
    @enderror

</div>

@push('scripts')
    <script>
        (() => {
            const password = document.getElementById('password');
            const confirmation = document.getElementById('password_confirmation');
            const toggle = document.getElementById('toggle-password');

            const show = (visible) => {
                password.type = confirmation.type = visible ? 'text' : 'password';
                toggle.textContent = visible ? 'Ocultar' : 'Mostrar';
            };

            toggle.addEventListener('click', () => show(password.type === 'password'));

            // Genera una contraseña de 10 caracteres sin letras confusas (l, 1, O, 0)
            document.getElementById('generate-password').addEventListener('click', () => {
                const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
                const values = crypto.getRandomValues(new Uint32Array(10));
                const generated = Array.from(values, (n) => chars[n % chars.length]).join('');

                password.value = confirmation.value = generated;
                show(true);
            });
        })();
    </script>
@endpush
