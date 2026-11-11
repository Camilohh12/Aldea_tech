{{-- Ayuda del campo Grupo (dentro de x-data="grupoAlumno(...)"), en el registro y en «Mi perfil» --}}
<p class="mt-2 text-xs text-slate-500" x-show="!delTutor && !codigo">
    Escribe solo el número de tu grupo: si estás en 1° grupo 2, escribe 2.
</p>
<p class="mt-2 text-xs text-slate-600" x-show="codigo" x-cloak>
    Tu grupo quedará como <strong class="font-mono font-semibold text-aldea-800" x-text="codigo"></strong>
</p>
<p class="mt-2 text-xs text-slate-600" x-show="delTutor" x-cloak>
    Con cuatrimestre «<span x-text="cuatrimestre"></span>» no escribes grupo: quedas en el grupo de tu tutor.
</p>
