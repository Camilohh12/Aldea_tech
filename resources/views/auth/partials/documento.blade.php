{{-- Nombre de un documento que el alumno acepta; con liga, se abre en otra pestaña --}}
@if (filled($url))
    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
       class="font-bold text-aldea-800 underline decoration-aldea-300 underline-offset-2 hover:text-aldea-600">{{ $nombre }}</a>
@else
    <strong class="text-aldea-800">{{ $nombre }}</strong>
@endif
