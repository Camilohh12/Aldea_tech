@props(['name', 'class' => 'h-4 w-4'])

@php
    $paths = [
        'reloj' => 'M12 7v5l3 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'ubicacion' => 'M12 21s-7-6.2-7-11.5A7 7 0 0119 9.5C19 14.8 12 21 12 21zM12 12a2.5 2.5 0 100-5 2.5 2.5 0 000 5z',
        'mas' => 'M12 8v8M8 12h8M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'calendario' => 'M8 2v3m8-3v3M3.5 9h17M5 4.5h14A1.5 1.5 0 0120.5 6v13.5A1.5 1.5 0 0119 21H5a1.5 1.5 0 01-1.5-1.5V6A1.5 1.5 0 015 4.5z',
        'check' => 'M9 12l2 2 4-4M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'prohibido' => 'M5.6 5.6l12.8 12.8M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'candado' => 'M7 11V8a5 5 0 0110 0v3M6 11h12a1 1 0 011 1v8a1 1 0 01-1 1H6a1 1 0 01-1-1v-8a1 1 0 011-1z',
        'buscar' => 'M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z',
        'persona' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM4 21a8 8 0 0116 0',
        'edificio' => 'M4 21V5a1 1 0 011-1h9a1 1 0 011 1v16M15 9h4a1 1 0 011 1v11M3 21h18M8 8h3M8 12h3M8 16h3',
        'qr' => 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 14h2v2h-2zM14 18h2v2h-2zM18 18h2v2h-2z',
        'flecha' => 'M5 12h14M13 6l6 6-6 6',
        'birrete' => 'M12 4L2 9l10 5 10-5-10-5zM6 11v5c3 2 9 2 12 0v-5',
        'escudo-check' => 'M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3zM9 12l2 2 4-4',
        'nube' => 'M7 18h10a4 4 0 00.5-7.97A6 6 0 006.1 9.1 4.5 4.5 0 007 18z',
        'sincronizar' => 'M4 4v5h5M20 20v-5h-5M5.5 15a7 7 0 0012 2.5M18.5 9a7 7 0 00-12-2.5',
        'wifi' => 'M2 8.5a15 15 0 0120 0M5 12a10 10 0 0114 0M8.5 15.5a5 5 0 017 0M12 19h.01',
        'teclado' => 'M3 6h18v12H3zM7 10h.01M11 10h.01M15 10h.01M7 14h10',
        'camara-off' => 'M3 3l18 18M10.5 6H15l1.5 2H19a1 1 0 011 1v8M16 19H5a1 1 0 01-1-1V9a1 1 0 011-1h2',
        'reloj-arena' => 'M7 3h10M7 21h10M8 3c0 5 8 5 8 9s-8 4-8 9M16 3c0 5-8 5-8 9',
        'diploma' => 'M7 3h10a2 2 0 012 2v9a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2zM8 7h8M8 10.5h5M10 16l-1.5 5L12 19.5 15.5 21 14 16',
        'descargar' => 'M12 4v11m0 0l-4-4m4 4l4-4M5 20h14',
        'brujula' => 'M21 12a9 9 0 11-18 0 9 9 0 0118 0zM15.5 8.5l-2 5-5 2 2-5 5-2z',
    ];
@endphp

<svg {{ $attributes->merge(['class' => $class]) }} fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $paths[$name] ?? '' }}" />
</svg>
