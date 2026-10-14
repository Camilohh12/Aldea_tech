@php
    $total = $activity->registered_count;
    $presentes = $activity->attended_count;
    $porcentaje = $total ? round($presentes / $total * 100) : 0;
@endphp

<a href="{{ route('pase-lista.show', $activity) }}"
   class="block rounded-2xl border border-aldea-100/80 bg-white p-4 shadow-sm transition hover:border-aldea-300 hover:shadow-md">
    <div class="flex items-center justify-between gap-2">
        <span class="text-sm font-bold text-aldea-800">{{ $activity->timeRange() }} hrs</span>
        @if ($activity->checkin_enabled)
            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">● Pase abierto</span>
        @else
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">Pase cerrado</span>
        @endif
    </div>
    <h3 class="mt-1.5 font-semibold leading-snug">{{ $activity->title }}</h3>
    <p class="mt-1 flex items-center gap-1.5 text-sm text-slate-600">
        <x-icono name="ubicacion" class="h-4 w-4" /> {{ $activity->room?->name ?? 'Sede por confirmar' }}
        @if ($activity->type) · {{ $activity->type->name }} @endif
    </p>
    <div class="mt-3 flex items-center justify-between text-[13px]">
        <span class="font-medium text-slate-700">Asistencia</span>
        <span class="font-semibold text-aldea-800">{{ $presentes }}/{{ $total }} alumnos</span>
    </div>
    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-aldea-100">
        <div class="h-full rounded-full bg-aldea-800" style="width: {{ $porcentaje }}%"></div>
    </div>
</a>
