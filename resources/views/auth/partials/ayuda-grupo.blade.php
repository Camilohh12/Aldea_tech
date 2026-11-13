{{-- Ayuda del campo Grupo (dentro de x-data="grupoAlumno(...)"), en el registro y en «Mi perfil» --}}
<p class="mt-2 text-xs text-slate-500" x-show="!delTutor">
    Elige tu grupo tal como aparece en la lista de la coordinación, por ejemplo TIID_01_02.
</p>
<p class="mt-2 text-xs text-slate-600" x-show="delTutor" x-cloak>
    Con «<span x-text="grupo || cuatrimestre"></span>» cuentas en el grupo de tu tutor.
</p>
