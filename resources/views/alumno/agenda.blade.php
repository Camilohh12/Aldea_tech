@php
    $chip = fn (bool $on) => $on
        ? 'whitespace-nowrap rounded-full bg-aldea-800 px-4 py-2 text-sm font-semibold text-white shadow-sm'
        : 'whitespace-nowrap rounded-full bg-aldea-100/70 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-aldea-100';
    $porcentaje = $horasMeta > 0 ? min(100, round($horasAcreditadas / $horasMeta * 100)) : 0;
    $fmtHoras = fn ($h) => rtrim(rtrim(number_format($h, 1), '0'), '.');
@endphp

<x-alumno-layout title="Mi Agenda" section="Mi Agenda">

    {{-- Resumen --}}
    <section class="mb-4 rounded-2xl bg-gradient-to-b from-aldea-100/80 to-aldea-50 p-3 shadow-sm">
        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-xl bg-white p-3">
                <p class="flex items-center gap-1.5 text-sm font-medium text-slate-700"><x-icono name="calendario" class="h-4 w-4" /> Inscritas</p>
                <p class="mt-1"><span class="text-2xl font-bold">{{ $total }}</span> <span class="text-sm text-slate-600">{{ $total === 1 ? 'actividad' : 'actividades' }}</span></p>
            </div>
            <div class="rounded-xl bg-white p-3">
                <p class="flex items-center gap-1.5 text-sm font-medium text-slate-700"><x-icono name="escudo-check" class="h-4 w-4" /> Asistencias</p>
                <p class="mt-1"><span class="text-2xl font-bold">{{ $attended }}</span> <span class="text-sm text-slate-600">{{ $attended === 1 ? 'registrada' : 'registradas' }}</span></p>
            </div>
        </div>

        <div class="mt-3 rounded-xl bg-white p-3">
            <div class="flex items-center justify-between text-sm">
                <p class="flex items-center gap-1.5 font-semibold"><x-icono name="birrete" class="h-4 w-4" /> Liberación curricular</p>
                <p class="font-bold">{{ $fmtHoras($horasAcreditadas) }} / {{ $horasMeta }} hrs</p>
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-aldea-100">
                <div class="h-full rounded-full bg-aldea-800" style="width: {{ $porcentaje }}%"></div>
            </div>
            <p class="mt-1.5 text-xs text-slate-600">Acredita al menos {{ $horasMeta }} horas de actividades con asistencia registrada para validar créditos extracurriculares.</p>
        </div>
    </section>

    {{-- Filtro por día --}}
    @if ($eventDays->count() > 1)
        <div class="-mx-4 mb-5 flex gap-2 overflow-x-auto px-4 pb-1">
            <a href="{{ route('alumno.agenda') }}" class="{{ $chip(! $filtro) }}">Todos los días</a>
            @foreach ($eventDays as $i => $date)
                <a href="{{ route('alumno.agenda', ['fecha' => $date]) }}" class="{{ $chip($filtro === $date) }}">
                    {{ \App\Models\Activity::shortDate($date) }} (Día {{ $i + 1 }})
                </a>
            @endforeach
        </div>
    @endif

    @if ($total === 0 && ! $filtro)
        <div class="rounded-2xl bg-aldea-50 px-6 py-10 text-center">
            <span class="mx-auto grid h-12 w-12 place-items-center rounded-xl bg-aldea-100 text-aldea-700"><x-icono name="calendario" class="h-6 w-6" /></span>
            <p class="mt-3 text-lg font-semibold">Aún no tienes actividades</p>
            <p class="mt-1 text-sm text-slate-600">Inscríbete desde el catálogo y aquí verás tu horario.</p>
            <a href="{{ route('alumno.catalogo') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-aldea-800 px-5 py-3 text-sm font-semibold text-white hover:bg-aldea-700">
                <x-icono name="brujula" class="h-4 w-4" /> Consultar actividades
            </a>
        </div>
    @else
        @foreach ($days as $day)
            @php
                $enrollments = $byDay->get($day, collect());
                $dayIndex = $eventDays->search($day);
                $fecha = $day === 'sin-fecha' ? null : \Illuminate\Support\Carbon::parse($day);
                $pasado = $fecha && $fecha->endOfDay()->isPast();
            @endphp

            <section class="mb-6">
                <h2 class="mb-3 flex items-baseline gap-2 text-lg font-semibold">
                    <span @class(['h-2 w-2 shrink-0 translate-y-[-2px] rounded-full', 'bg-aldea-800' => $enrollments->isNotEmpty(), 'bg-slate-300' => $enrollments->isEmpty()])></span>
                    <span @class(['text-aldea-900' => $enrollments->isNotEmpty(), 'text-slate-700' => $enrollments->isEmpty()])>
                        {{ $fecha ? \Illuminate\Support\Str::ucfirst($fecha->locale('es')->isoFormat('dddd, D [de] MMMM')) : 'Fecha por definir' }}
                    </span>
                    @if ($dayIndex !== false)
                        <span class="text-sm font-normal text-slate-500">(Día {{ $dayIndex + 1 }})</span>
                    @endif
                </h2>

                @if ($enrollments->isEmpty())
                    <div class="rounded-2xl bg-aldea-50 px-6 py-8 text-center">
                        <span class="mx-auto grid h-11 w-11 place-items-center rounded-xl bg-aldea-100 text-aldea-700"><x-icono name="calendario" class="h-5 w-5" /></span>
                        <p class="mt-3 font-semibold">Sin actividades para este día</p>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ $pasado ? 'No tuviste actividades registradas este día.' : 'Aún no tienes actividades registradas para este día. ¡Explora el catálogo!' }}
                        </p>
                        @unless ($pasado)
                            <a href="{{ route('alumno.catalogo', ['fecha' => $day]) }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-aldea-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-aldea-700">
                                <x-icono name="brujula" class="h-4 w-4" /> Consultar actividades
                            </a>
                        @endunless
                    </div>
                @else
                    <ol class="space-y-4">
                        @foreach ($enrollments as $enrollment)
                            @php
                                $activity = $enrollment->activity;
                                $enCurso = $activity->isInProgress();
                                $termino = $activity->hasEnded();
                            @endphp
                            <li class="flex gap-3">
                                {{-- Hora y línea de tiempo --}}
                                <div class="flex w-12 shrink-0 flex-col items-center">
                                    <p class="text-sm font-bold">{{ substr($activity->start_time ?? '--:--', 0, 5) }}</p>
                                    <p class="text-xs text-slate-500">{{ substr($activity->end_time ?? '--:--', 0, 5) }}</p>
                                    <span class="mt-2 w-0.5 flex-1 rounded-full bg-aldea-100"></span>
                                </div>

                                <article class="min-w-0 flex-1 rounded-2xl border border-aldea-100/80 bg-white p-4 shadow-sm">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        @if ($activity->activityType)
                                            <span class="rounded-full bg-aldea-100 px-2.5 py-1 text-xs font-semibold text-aldea-800">{{ $activity->activityType->name }}</span>
                                        @endif

                                        @if ($enrollment->attended)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                                <x-icono name="check" class="h-3.5 w-3.5" /> Acreditada
                                            </span>
                                        @elseif ($enCurso)
                                            <span class="rounded-full bg-aldea-100 px-2.5 py-1 text-xs font-semibold text-aldea-800">● En curso</span>
                                        @elseif ($termino)
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">Sin asistencia</span>
                                        @else
                                            <span class="rounded-full bg-aldea-50 px-2.5 py-1 text-xs font-semibold text-aldea-700">Próxima actividad</span>
                                        @endif
                                    </div>

                                    <h3 class="mt-2 text-[17px] font-semibold leading-snug">{{ $activity->title }}</h3>

                                    <p class="mt-2 flex items-center gap-2 text-sm text-slate-600">
                                        <x-icono name="ubicacion" class="h-4 w-4 shrink-0" /> {{ $activity->room?->name ?? 'Sede por confirmar' }}
                                    </p>
                                    <p class="mt-1 flex items-center gap-2 text-sm text-slate-600">
                                        <x-icono name="persona" class="h-4 w-4 shrink-0" /> {{ $activity->speakerNames() ?: 'Ponente por confirmar' }}
                                    </p>

                                    @if ($enrollment->attended)
                                        <p class="mt-3 flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">
                                            <x-icono name="check" class="h-4 w-4" /> Asistencia registrada · {{ $enrollment->checkin_time?->format('H:i') }} hrs
                                        </p>
                                    @elseif (! $termino)
                                        <a href="{{ route('alumno.pases') }}#pase-{{ $enrollment->id }}" class="mt-3 flex items-center justify-between rounded-lg bg-aldea-50 px-3 py-2.5 text-sm font-semibold text-aldea-900 hover:bg-aldea-100">
                                            <span class="flex items-center gap-2"><x-icono name="qr" class="h-4 w-4" /> Mostrar mi pase QR</span>
                                            <x-icono name="flecha" class="h-4 w-4" />
                                        </a>

                                        @unless ($activity->hasStarted())
                                            <div class="mt-3 flex items-center justify-between gap-2 border-t border-slate-100 pt-3 text-xs text-slate-600">
                                                <span class="flex items-center gap-1"><x-icono name="reloj-arena" class="h-3.5 w-3.5" /> Tolerancia 15 min</span>
                                                <form method="POST" action="{{ route('alumno.inscripcion.destroy', $activity) }}"
                                                      onsubmit="return confirm('¿Cancelar tu lugar en esta actividad? Otro alumno podrá tomarlo y tu pase QR dejará de servir.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="flex items-center gap-1 font-medium hover:text-red-600">
                                                        <x-icono name="prohibido" class="h-3.5 w-3.5" /> Cancelar inscripción
                                                    </button>
                                                </form>
                                            </div>
                                        @endunless
                                    @endif
                                </article>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        @endforeach
    @endif

    @if ($tieneActividadHoy)
        <x-slot name="floating">
            <div class="pointer-events-none fixed inset-x-0 bottom-20 z-20 flex justify-center px-4" style="margin-bottom: env(safe-area-inset-bottom)">
                <a href="{{ route('alumno.pases') }}" class="pointer-events-auto flex items-center gap-2 rounded-2xl bg-aldea-800 px-6 py-3.5 text-[15px] font-bold text-white shadow-lg shadow-aldea-900/30 hover:bg-aldea-700">
                    <x-icono name="qr" class="h-5 w-5" /> Mostrar mi pase de hoy
                </a>
            </div>
        </x-slot>
    @endif
</x-alumno-layout>
