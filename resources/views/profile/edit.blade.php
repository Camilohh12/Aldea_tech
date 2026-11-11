<x-app-layout>
    <x-slot name="title">Mi perfil · Aldea Tecnológica UPP</x-slot>

    @php
        $roles = [
            'super_admin' => 'Super administrador',
            'admin' => 'Administrador',
            'tutor' => 'Tutor académico',
            'responsable' => 'Responsable',
            'docente' => 'Docente',
            'ponente' => 'Ponente',
            'alumno' => 'Alumno',
        ];
        $rol = $user->getRoleNames()->map(fn ($nombre) => $roles[$nombre] ?? ucfirst($nombre))->first();

        // Estilos compartidos por los formularios de las tarjetas
        $tarjeta = 'rounded-2xl bg-white p-5 shadow-sm ring-1 ring-aldea-100 sm:p-8';
    @endphp

    <div class="mx-auto max-w-3xl space-y-6">
        {{-- Resumen de la cuenta --}}
        <section class="{{ $tarjeta }}">
            <div class="flex items-center gap-4">
                <x-iniciales :name="$user->name" size="h-14 w-14 text-lg" />
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-aldea-600">Mi perfil</p>
                    <h1 class="truncate font-heading text-xl font-bold text-upp-primary sm:text-2xl">{{ $user->name }}</h1>
                    <p class="truncate text-sm text-slate-500">{{ $user->email }}</p>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                @if ($rol)
                    <span class="rounded-full bg-aldea-800 px-3 py-1 text-white">{{ $rol }}</span>
                @endif
                @if ($user->matricula)
                    <span class="rounded-full bg-aldea-50 px-3 py-1 text-aldea-800">Matrícula {{ $user->matricula }}</span>
                @endif
                @if ($user->carrera)
                    <span class="rounded-full bg-aldea-50 px-3 py-1 text-aldea-800">{{ $user->carrera }}</span>
                @endif
                @if ($user->cuatrimestre)
                    <span class="rounded-full bg-aldea-50 px-3 py-1 text-aldea-800">{{ is_numeric($user->cuatrimestre) ? $user->cuatrimestre.'° cuatrimestre' : 'Cuatrimestre: '.$user->cuatrimestre }}</span>
                @endif
            </div>
        </section>

        <div class="{{ $tarjeta }}">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="{{ $tarjeta }}">
            @include('profile.partials.update-password-form')
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-red-100 sm:p-8">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>
