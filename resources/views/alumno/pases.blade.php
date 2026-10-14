<x-alumno-layout title="Mis pases" section="Mis pases">
    <x-slot name="head">
        @vite('resources/js/pases.js')
    </x-slot>

    <section class="mb-4 rounded-2xl bg-gradient-to-b from-aldea-100/80 to-aldea-50 p-4">
        <p class="flex items-center gap-2 font-semibold text-aldea-900"><x-icono name="qr" class="h-5 w-5" /> Tu pase de asistencia</p>
        <p class="mt-1 text-sm text-slate-600">
            Cada actividad a la que te inscribes tiene un código único. Al llegar al aula, muéstralo al tutor para que lo escanee.
            Sube el brillo de la pantalla para que se lea más rápido.
        </p>
    </section>

    @forelse ($pases as $pase)
        @php
            $activity = $pase->activity;
            $termino = $activity->hasEnded();
            $enCurso = $activity->isInProgress();
        @endphp

        <article id="pase-{{ $pase->id }}" @class([
            'mb-4 scroll-mt-28 overflow-hidden rounded-2xl border bg-white shadow-sm',
            'border-aldea-300 ring-2 ring-aldea-200' => $enCurso && ! $pase->attended,
            'border-aldea-100/80' => ! ($enCurso && ! $pase->attended),
        ])>
            <div class="flex items-center justify-between gap-2 border-b border-aldea-100/70 bg-aldea-50/60 px-4 py-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-aldea-700">
                        {{ $activity->type?->name ?? 'Actividad' }} ·
                        {{ \App\Models\Activity::shortDate($activity->date) ?? 'Fecha por definir' }} · {{ $activity->timeRange() }} hrs
                    </p>
                    <h2 class="truncate text-base font-bold">{{ $activity->title }}</h2>
                </div>
                @if ($pase->attended)
                    <span class="shrink-0 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">✓ Registrada</span>
                @elseif ($enCurso)
                    <span class="shrink-0 rounded-full bg-aldea-800 px-2.5 py-1 text-xs font-bold text-white">● Ahora</span>
                @elseif ($termino)
                    <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-500">Finalizada</span>
                @else
                    <span class="shrink-0 rounded-full bg-aldea-100 px-2.5 py-1 text-xs font-bold text-aldea-800">Próxima</span>
                @endif
            </div>

            <div class="flex flex-col items-center px-4 py-5">
                <div class="relative">
                    <canvas data-qr="{{ $pase->qr_token }}" width="240" height="240"
                            @class(['h-60 w-60 rounded-xl', 'opacity-20' => $pase->attended || $termino])
                            role="img" aria-label="Código QR de tu pase para {{ $activity->title }}"></canvas>

                    @if ($pase->attended)
                        <div class="absolute inset-0 grid place-items-center">
                            <div class="rounded-2xl bg-white/95 px-5 py-3 text-center shadow">
                                <x-icono name="check" class="mx-auto h-8 w-8 text-emerald-600" />
                                <p class="mt-1 font-bold text-emerald-700">Asistencia registrada</p>
                                <p class="text-sm text-slate-600">{{ $pase->checkin_time?->format('H:i') }} hrs</p>
                            </div>
                        </div>
                    @elseif ($termino)
                        <div class="absolute inset-0 grid place-items-center">
                            <p class="rounded-2xl bg-white/95 px-5 py-3 text-center text-sm font-semibold text-slate-600 shadow">Actividad finalizada<br>sin asistencia</p>
                        </div>
                    @endif
                </div>

                <p class="mt-3 font-mono text-xs tracking-wider text-slate-500" title="Código del pase">
                    {{ implode(' ', str_split(strtoupper(substr($pase->qr_token, 0, 12)), 4)) }}…
                </p>

                <dl class="mt-4 grid w-full grid-cols-2 gap-3 text-sm">
                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                        <dt class="text-xs text-slate-500">Alumno</dt>
                        <dd class="truncate font-semibold">{{ auth()->user()->name }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                        <dt class="text-xs text-slate-500">Sede</dt>
                        <dd class="truncate font-semibold">{{ $activity->room?->name ?? 'Por confirmar' }}</dd>
                    </div>
                </dl>
            </div>
        </article>
    @empty
        <div class="rounded-2xl bg-aldea-50 px-6 py-10 text-center">
            <span class="mx-auto grid h-12 w-12 place-items-center rounded-xl bg-aldea-100 text-aldea-700"><x-icono name="qr" class="h-6 w-6" /></span>
            <p class="mt-3 text-lg font-semibold">Aún no tienes pases</p>
            <p class="mt-1 text-sm text-slate-600">Cuando te inscribas a una actividad, aquí aparecerá su código QR.</p>
            <a href="{{ route('alumno.catalogo') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-aldea-800 px-5 py-3 text-sm font-semibold text-white hover:bg-aldea-700">
                <x-icono name="brujula" class="h-4 w-4" /> Consultar actividades
            </a>
        </div>
    @endforelse
</x-alumno-layout>
