@php
    $alumno = $progreso->alumno;
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 1), '0'), '.');
@endphp

<x-tutor-layout :title="$alumno->name">
    <a href="{{ route('tutor.tutorados') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-upp-secondary hover:text-upp-primary-dark">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
        Volver a mis tutorados
    </a>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Datos del alumno y progreso -->
        <aside class="space-y-6">
            <section class="rounded-xl bg-white p-5 shadow-sm">
                <div class="flex items-center gap-4">
                    <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-upp-primary font-heading text-lg font-bold text-white">
                        {{ collect(preg_split('/\s+/', trim($alumno->name)))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') }}
                    </span>
                    <div class="min-w-0">
                        <h1 class="font-heading text-xl font-bold leading-tight text-upp-primary">{{ $alumno->name }}</h1>
                        <div class="mt-1">@include('tutor._estado', ['progreso' => $progreso])</div>
                    </div>
                </div>

                <dl class="mt-5 space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Matrícula</dt><dd class="font-medium">{{ $alumno->matricula ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Carrera</dt><dd class="text-right font-medium">{{ $alumno->carrera ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Cuatrimestre</dt><dd class="font-medium">{{ is_numeric($alumno->cuatrimestre) ? $alumno->cuatrimestre.'°' : ($alumno->cuatrimestre ?? '—') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Situación</dt><dd class="font-medium">{{ \Illuminate\Support\Str::ucfirst($alumno->estatus ?? 'regular') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Correo</dt><dd class="truncate font-medium"><a href="mailto:{{ $alumno->email }}" class="text-upp-primary-container hover:underline">{{ $alumno->email }}</a></dd></div>
                </dl>
            </section>

            <section class="rounded-xl bg-white p-5 shadow-sm">
                <h2 class="font-heading text-base font-bold text-upp-primary">Liberación curricular</h2>
                <p class="mt-3 font-heading text-4xl font-bold text-upp-primary">{{ $fmt($progreso->horas) }} <span class="text-lg font-semibold text-gray-400">/ {{ $fmt($progreso->meta) }} hrs</span></p>
                <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-upp-surface-high">
                    <div class="h-full rounded-full {{ $progreso->estado === 'acreditado' ? 'bg-emerald-500' : 'bg-upp-primary-container' }}" style="width: {{ $progreso->porcentaje }}%"></div>
                </div>
                <p class="mt-2 text-xs text-gray-500">{{ $progreso->porcentaje }}% de la meta · solo cuentan asistencias validadas</p>

                <div class="mt-5 grid grid-cols-2 gap-3 text-center">
                    <div class="rounded-lg bg-upp-surface-low p-3">
                        <p class="font-heading text-2xl font-bold text-upp-primary">{{ $progreso->asistencias }}</p>
                        <p class="text-xs text-gray-500">Asistencias validadas</p>
                    </div>
                    <div class="rounded-lg bg-upp-surface-low p-3">
                        <p class="font-heading text-2xl font-bold text-upp-primary">{{ $progreso->inscripcionesActivas }}</p>
                        <p class="text-xs text-gray-500">Inscripciones activas</p>
                    </div>
                </div>
            </section>
        </aside>

        <!-- Historial -->
        <section class="overflow-hidden rounded-xl bg-white shadow-sm lg:col-span-2">
            <div class="border-b border-upp-surface-high px-4 py-4 lg:px-6">
                <h2 class="font-heading text-lg font-bold text-upp-primary">Historial de actividades y asistencias</h2>
                <p class="text-sm text-gray-500">Todas las inscripciones del alumno, en orden cronológico.</p>
            </div>

            @php
                $estadoInscripcion = function ($e) {
                    if (! $e->isRegistered()) {
                        return ['Cancelada', 'bg-gray-100 text-gray-500'];
                    }
                    if ($e->attended) {
                        return ['Asistencia validada', 'bg-emerald-50 text-emerald-700'];
                    }
                    if ($e->activity->hasEnded()) {
                        return ['Sin asistencia', 'bg-red-50 text-red-700'];
                    }
                    if ($e->activity->isInProgress()) {
                        return ['En curso', 'bg-amber-50 text-amber-700'];
                    }

                    return ['Próxima', 'bg-upp-surface-high text-upp-primary-container'];
                };
            @endphp

            <ul class="divide-y divide-upp-surface-high">
                @forelse ($progreso->inscripciones as $e)
                    @php
                        $a = $e->activity;
                        [$texto, $clase] = $estadoInscripcion($e);
                        $cuenta = $e->isRegistered() && $e->attended;
                    @endphp
                    <li class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between lg:px-6">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-upp-secondary">
                                {{ $a->activityType?->name ?? 'Actividad' }} ·
                                {{ \Illuminate\Support\Str::ucfirst($a->date?->locale('es')->isoFormat('ddd D [de] MMM') ?? 'Sin fecha') }} · {{ $a->timeRange() }} h
                            </p>
                            <p class="mt-0.5 font-semibold leading-snug">{{ $a->title }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $a->room?->name ?? 'Sede por confirmar' }}@if ($a->speakers->isNotEmpty()) · {{ $a->speakerNames() }}@endif
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-3 sm:flex-col sm:items-end sm:gap-1">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $clase }}">
                                {{ $texto }}@if ($e->attended && $e->checkin_time) · {{ $e->checkin_time->format('H:i') }}@endif
                            </span>
                            <span class="text-xs {{ $cuenta ? 'font-semibold text-upp-primary' : 'text-gray-400' }}">
                                {{ $cuenta ? '+'.$fmt($a->creditedHours()).' hrs' : '0 hrs' }}
                            </span>
                        </div>
                    </li>
                @empty
                    <li class="px-6 py-12 text-center text-sm text-gray-500">El alumno aún no se inscribe a ninguna actividad.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-tutor-layout>
