{{-- Cambia entre la asistencia por taller y por grupo. Uso: @include('admin.attendances._vistas', ['activa' => 'grupos']) --}}
@php
    $vistas = [
        'talleres' => ['Por taller', route('admin.attendances.index')],
        'grupos' => ['Por grupo', route('admin.attendances.groups')],
    ];
@endphp

<nav class="mb-8 inline-flex gap-1 rounded-xl bg-white p-1 shadow-sm" aria-label="Ver asistencias">
    @foreach ($vistas as $clave => [$etiqueta, $url])
        <a href="{{ $url }}" @if ($activa === $clave) aria-current="page" @endif
           class="px-4 py-2 rounded-lg text-sm font-semibold transition
                  {{ $activa === $clave ? 'bg-upp-primary-container text-white shadow-sm' : 'text-upp-primary-container hover:bg-upp-surface-low' }}">
            {{ $etiqueta }}
        </a>
    @endforeach
</nav>
