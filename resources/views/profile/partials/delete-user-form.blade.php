<section>
    <header>
        <h2 class="font-heading text-lg font-bold text-red-700">Eliminar cuenta</h2>
        <p class="mt-1 text-sm text-slate-500">
            Se borran tu cuenta, tus inscripciones y tu registro de asistencias. Esta acción no se puede deshacer.
        </p>
    </header>

    <button type="button"
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
            class="mt-5 inline-flex items-center justify-center rounded-xl border border-red-200 px-5 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50">
        Eliminar mi cuenta
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" maxWidth="md" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="font-heading text-lg font-bold text-upp-primary">¿Seguro que quieres eliminar tu cuenta?</h2>
            <p class="mt-1 text-sm text-slate-500">Escribe tu contraseña para confirmarlo. Perderás tus inscripciones y asistencias.</p>

            <div class="mt-5">
                <label for="password" class="sr-only">Contraseña</label>
                <input id="password" name="password" type="password" placeholder="Tu contraseña" autocomplete="current-password"
                       class="w-full rounded-xl border-slate-200 py-2.5 text-sm focus:border-red-500 focus:ring-red-500">
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-1.5" />
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" x-on:click="$dispatch('close')"
                        class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Cancelar
                </button>
                <button type="submit"
                        class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                    Eliminar cuenta
                </button>
            </div>
        </form>
    </x-modal>
</section>
