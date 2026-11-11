@extends('layouts.admin')

@section('title', 'Administradores')

@section('content')

@php
    $me = auth()->user();
    $iAmSuperAdmin = $me->hasRole('super_admin');

    // Nombres para mostrar de los demás roles de la cuenta
    $roleLabels = [
        'tutor' => 'Tutor',
        'docente' => 'Docente',
        'alumno' => 'Alumno',
        'ponente' => 'Ponente',
        'responsable' => 'Responsable',
    ];
@endphp

    <!-- Encabezado -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-8">

        <div>
            <p class="text-xs font-semibold tracking-widest uppercase text-upp-secondary">
                Administración / <span class="text-upp-primary">Administradores</span>
            </p>

            <h1 class="font-heading text-3xl sm:text-4xl font-bold text-upp-primary mt-2">
                Administradores
            </h1>

            <p class="text-gray-600 mt-2">
                Personas con acceso completo al panel. Cada una entra con su propio usuario.
            </p>
        </div>

        <a href="{{ route('admin.administrators.create') }}"
           class="inline-flex items-center justify-center gap-2 shrink-0 whitespace-nowrap bg-upp-primary-container hover:bg-upp-primary-dark text-white px-5 py-3 rounded-lg font-medium shadow-sm transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Nuevo Administrador
        </a>

    </div>

    <!-- Tarjetas de resumen -->
    <div class="grid gap-4 sm:grid-cols-2 mb-8">

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <div class="flex items-start justify-between">
                <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Cuentas con acceso al panel</p>
                <span class="p-2 rounded-lg bg-upp-surface-high text-upp-primary-container">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </span>
            </div>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $total }}</p>
            <p class="text-sm text-upp-secondary mt-1">Administradores y Super Admin</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-l-4 border-upp-primary-container p-5">
            <div class="flex items-start justify-between">
                <p class="text-xs font-semibold tracking-wider uppercase text-gray-600">Super Admin</p>
                <span class="p-2 rounded-lg bg-upp-surface-high text-upp-primary-container">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </span>
            </div>
            <p class="font-heading text-4xl font-bold text-upp-primary mt-2">{{ $superAdmins }}</p>
            <p class="text-sm text-upp-secondary mt-1">Solo otro Super Admin puede modificarlas</p>
        </div>

    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">

        <div class="px-4 lg:px-6 py-4 border-b border-upp-surface-high flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <h2 class="font-heading text-lg font-bold text-upp-primary">Cuentas registradas</h2>

            <!-- Búsqueda -->
            <form action="{{ route('admin.administrators.index') }}" method="GET" data-filtro-vivo class="flex gap-2 w-full md:w-auto">
                <div class="relative flex-1 md:w-80">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" />
                    </svg>
                    <input type="search" name="q" value="{{ $search }}"
                           placeholder="Buscar por nombre o correo..."
                           class="w-full rounded-lg border border-gray-300 bg-white pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-upp-primary-container">
                </div>

                <button type="submit"
                        class="px-4 py-2 rounded-lg bg-upp-surface-high text-upp-primary-container text-sm font-medium hover:bg-upp-secondary-container transition">
                    Buscar
                </button>

                <span data-resultados="limpiar" class="contents">
                @if ($search !== '')
                    <a href="{{ route('admin.administrators.index') }}"
                       class="px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-upp-surface-low transition">
                        Limpiar
                    </a>
                @endif
                </span>
            </form>
        </div>

        <div class="overflow-x-auto" data-resultados="tabla">
            <table class="w-full text-left text-sm">

                <thead class="bg-upp-surface-low text-upp-secondary text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Administrador</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Rol</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Otros roles</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold">Alta</th>
                        <th class="px-4 lg:px-6 py-3 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-upp-surface-high">
                    @forelse ($administrators as $administrator)
                        @php
                            $words = preg_split('/\s+/', trim($administrator->name));
                            $initials = mb_strtoupper(mb_substr($words[0] ?? '', 0, 1) . mb_substr($words[1] ?? '', 0, 1));
                            $roleNames = $administrator->roles->pluck('name');
                            $isSuperAdmin = $roleNames->contains('super_admin');
                            $isSelf = $me->is($administrator);
                            $otherRoles = $roleNames->reject(fn ($role) => in_array($role, ['admin', 'super_admin']));

                            $canEdit = ! $isSuperAdmin || $iAmSuperAdmin;
                            $canDelete = ! $isSelf && ! $isSuperAdmin && $total > 1;
                            $deleteReason = $isSelf ? 'No puedes eliminar tu propia cuenta'
                                : ($isSuperAdmin ? 'Las cuentas de Super Admin no se eliminan desde aquí' : 'Debe quedar al menos un administrador');
                            $confirmMessage = $otherRoles->isNotEmpty()
                                ? "¿Quitar el acceso de administrador a {$administrator->name}? Su cuenta se conserva."
                                : "¿Eliminar la cuenta de {$administrator->name}?";
                        @endphp
                        <tr class="hover:bg-upp-surface/60">

                            <td class="px-4 lg:px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="w-9 h-9 rounded-full bg-upp-surface-high text-upp-primary-container flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ $initials }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-upp-primary">
                                            {{ $administrator->name }}
                                            @if ($isSelf)
                                                <span class="ml-1 inline-flex px-1.5 py-0.5 rounded text-[11px] font-semibold bg-upp-primary-container text-white align-middle">Tú</span>
                                            @endif
                                        </p>
                                        <p class="text-xs text-gray-500">{{ $administrator->email }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                @if ($isSuperAdmin)
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-upp-primary-container text-white">Super Admin</span>
                                @else
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-upp-secondary-container/60 text-upp-primary-container">Administrador</span>
                                @endif
                            </td>

                            <td class="px-4 lg:px-6 py-4">
                                @forelse ($otherRoles as $role)
                                    <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-700 mr-1">
                                        {{ $roleLabels[$role] ?? ucfirst($role) }}
                                    </span>
                                @empty
                                    <span class="text-gray-400">—</span>
                                @endforelse
                            </td>

                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-gray-600">
                                {{ $administrator->created_at?->format('d/m/Y') }}
                            </td>

                            <td class="px-4 lg:px-6 py-4">
                                <div class="flex justify-end items-center gap-2">

                                    @if ($canEdit)
                                        <a href="{{ route('admin.administrators.edit', $administrator) }}"
                                           class="px-3 py-1.5 rounded-lg border border-upp-surface-high text-upp-primary-container font-medium hover:bg-upp-surface-low transition">
                                            Editar
                                        </a>
                                    @endif

                                    @if ($canDelete)
                                        <form action="{{ route('admin.administrators.destroy', $administrator) }}"
                                              method="POST"
                                              onsubmit="return confirm(@js($confirmMessage))">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    class="px-3 py-1.5 rounded-lg text-red-600 font-medium hover:bg-red-50 transition whitespace-nowrap">
                                                {{ $otherRoles->isNotEmpty() ? 'Quitar acceso' : 'Eliminar' }}
                                            </button>
                                        </form>
                                    @else
                                        <span class="px-3 py-1.5 rounded-lg text-gray-400 cursor-not-allowed whitespace-nowrap"
                                              title="{{ $deleteReason }}">
                                            Eliminar
                                        </span>
                                    @endif

                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 lg:px-6 py-12 text-center text-gray-500">
                                No se encontraron administradores para «{{ $search }}».
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

    </div>

    <!-- Paginación -->
    <div class="mt-6" data-resultados="paginacion">
        {{ $administrators->links() }}
    </div>

    <!-- Nota -->
    <div class="flex items-start gap-4 bg-upp-surface-low border border-upp-surface-high rounded-xl p-5 mt-6 text-sm">
        <span class="p-2 rounded-lg bg-white text-upp-primary-container shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </span>
        <p class="text-gray-600">
            Todos los administradores tienen el mismo acceso: programación, asistencias, certificados, reportes y gestión.
            Por seguridad nadie puede eliminar su propia cuenta y siempre debe quedar al menos un administrador.
        </p>
    </div>

@endsection
