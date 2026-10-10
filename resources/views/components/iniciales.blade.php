@props(['name' => '', 'size' => 'h-9 w-9 text-xs'])

@php
    // Quita títulos como "Ing.", "Dr.", "Mtra." y toma las dos primeras iniciales.
    $initials = collect(preg_split('/\s+/', trim((string) $name)))
        ->reject(fn ($p) => $p === '' || str_ends_with($p, '.'))
        ->take(2)
        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
        ->implode('');
@endphp

<span {{ $attributes->class(["grid shrink-0 place-items-center rounded-full bg-gradient-to-br from-aldea-600 to-aldea-900 font-bold text-white", $size]) }} aria-hidden="true">
    {{ $initials ?: '?' }}
</span>
