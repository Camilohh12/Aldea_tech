@php
    $icono = 'pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-aldea-600';
    $input = 'w-full rounded-xl border-slate-200 py-2.5 pl-10 pr-11 text-sm placeholder:text-slate-400 focus:border-aldea-500 focus:ring-aldea-500';
    $ojo = 'absolute right-2 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-lg text-slate-500 hover:bg-aldea-50';
    $campos = [
        ['update_password_current_password', 'current_password', 'Contraseña actual', 'current-password', 'candado', ''],
        ['update_password_password', 'password', 'Contraseña nueva', 'new-password', 'candado', 'Mínimo 8 caracteres'],
        ['update_password_password_confirmation', 'password_confirmation', 'Confirmar contraseña nueva', 'new-password', 'sincronizar', 'Repite la contraseña nueva'],
    ];
@endphp

<section>
    <header>
        <h2 class="font-heading text-lg font-bold text-upp-primary">Cambiar contraseña</h2>
        <p class="mt-1 text-sm text-slate-500">Usa una contraseña larga que no ocupes en otros sitios.</p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('put')

        @foreach ($campos as [$id, $nombre, $etiqueta, $autocompletar, $icon, $ayuda])
            <div x-data="{ ver: false }">
                <label for="{{ $id }}" class="text-sm font-semibold">{{ $etiqueta }}</label>
                <div class="relative mt-1.5">
                    <x-icono :name="$icon" :class="$icono" />
                    <input id="{{ $id }}" name="{{ $nombre }}" :type="ver ? 'text' : 'password'" type="password" autocomplete="{{ $autocompletar }}"
                           placeholder="{{ $ayuda }}" class="{{ $input }}">
                    <button type="button" @click="ver = !ver" :aria-label="ver ? 'Ocultar contraseña' : 'Mostrar contraseña'" class="{{ $ojo }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zM12 15a3 3 0 100-6 3 3 0 000 6z" /></svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->updatePassword->get($nombre)" class="mt-1.5" />
            </div>
        @endforeach

        <div class="flex flex-wrap items-center gap-4">
            <button type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-aldea-800 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-aldea-700">
                Actualizar contraseña
            </button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                   class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700">
                    <x-icono name="check" class="h-4 w-4" /> Contraseña actualizada.
                </p>
            @endif
        </div>
    </form>
</section>
