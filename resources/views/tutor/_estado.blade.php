@php
    $estilos = [
        'acreditado' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'en_progreso' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'pendiente' => 'bg-red-50 text-red-700 ring-red-200',
    ];
@endphp
<span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $estilos[$progreso->estado] }}">
    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
    {{ $progreso->etiqueta() }}
</span>
