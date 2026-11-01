{{-- Campos del administrador, compartidos por create y edit.
     Recibe $administrator (null al registrar). --}}
@php
    $field = 'w-full rounded-lg border px-4 py-3 bg-white focus:outline-none focus:ring-2 focus:ring-upp-primary-container';
    $border = fn ($name) => $errors->has($name) ? 'border-red-400' : 'border-gray-300';
@endphp

<!-- Nombre -->
<div class="mb-6">
    <label for="name" class="block text-sm font-semibold text-upp-primary mb-2">
        Nombre completo <span class="text-red-600">*</span>
    </label>
    <input type="text" id="name" name="name" maxlength="255" required autocomplete="off"
           value="{{ old('name', $administrator?->name) }}"
           placeholder="Ej. Mtra. Patricia Hernández Ruiz"
           class="{{ $field }} {{ $border('name') }}">
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
           value="{{ old('email', $administrator?->email) }}"
           placeholder="Ej. phernandez@upp.edu.mx"
           class="{{ $field }} {{ $border('email') }}">
    <p class="text-xs mt-2 text-gray-500">Con este correo iniciará sesión en el panel.</p>
    @error('email')
        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
    @enderror
</div>

<!-- Contraseña -->
<div class="mb-8 rounded-xl bg-upp-surface-low p-5">

    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
        <p class="text-sm font-semibold text-upp-primary">
            @if ($administrator)
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
                   @required(! $administrator)
                   class="{{ $field }} {{ $border('password') }}">
        </div>
        <div>
            <label for="password_confirmation" class="block text-xs font-semibold text-gray-600 mb-1">Confirmar contraseña</label>
            <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" autocomplete="new-password"
                   @required(! $administrator)
                   class="{{ $field }} {{ $border('password') }}">
        </div>
    </div>

    <p class="text-xs mt-3 text-gray-500">
        @if ($administrator)
            Déjala vacía para conservar la contraseña actual.
        @else
            Mínimo 8 caracteres. Compártela con la persona; podrá cambiarla desde «Mi perfil».
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
