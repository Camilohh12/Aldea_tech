@php
    $total = $inscritos->count();
    $presentes = $inscritos->where('attended', true)->count();
@endphp

<x-staff-layout :title="'Pase de lista · '.$activity->title" :back="route('pase-lista.index')">
    <x-slot name="head">
        @vite('resources/js/pase-lista.js')
    </x-slot>

    {{-- Actividad --}}
    <section class="mb-4 rounded-2xl border border-aldea-100/80 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold text-aldea-700">
            {{ $activity->activityType?->name ?? 'Actividad' }} ·
            {{ \App\Models\Activity::shortDate($activity->date) ?? 'Fecha por definir' }} · {{ $activity->timeRange() }} hrs
        </p>
        <h1 class="mt-1 text-lg font-bold leading-snug">{{ $activity->title }}</h1>
        <p class="mt-1 flex items-center gap-1.5 text-sm text-slate-600">
            <x-icono name="ubicacion" class="h-4 w-4" /> {{ $activity->room?->name ?? 'Sede por confirmar' }}
        </p>

        <div class="mt-4 flex items-center justify-between gap-3 rounded-xl bg-aldea-50 px-3 py-2.5">
            <div class="text-sm">
                <p class="font-semibold">
                    @if ($activity->checkin_enabled)
                        <span class="text-emerald-700">● Pase de lista abierto</span>
                    @else
                        <span class="text-slate-600">Pase de lista cerrado</span>
                    @endif
                </p>
                <p class="text-xs text-slate-500">
                    {{ $activity->checkin_enabled ? 'Ya puedes escanear los pases.' : 'Ábrelo para registrar asistencias en línea.' }}
                </p>
            </div>
            <form method="POST" action="{{ route('pase-lista.toggle', $activity) }}">
                @csrf
                <button type="submit" @class([
                    'rounded-lg px-3 py-2 text-xs font-bold',
                    'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' => $activity->checkin_enabled,
                    'bg-aldea-800 text-white hover:bg-aldea-700' => ! $activity->checkin_enabled,
                ])>
                    {{ $activity->checkin_enabled ? 'Cerrar pase' : 'Abrir pase de lista' }}
                </button>
            </form>
        </div>
    </section>

    <div id="pase-lista-app"
         data-store-url="{{ route('pase-lista.store', $activity) }}"
         data-sync-url="{{ route('pase-lista.sync', $activity) }}"
         data-activity-id="{{ $activity->id }}"
         data-staff-id="{{ auth()->id() }}">

        <script type="application/json" id="roster">@json($roster, JSON_UNESCAPED_UNICODE)</script>

        {{-- Contadores y conexión --}}
        <section class="mb-4 grid grid-cols-2 gap-3">
            <div class="rounded-2xl border border-aldea-100/80 bg-white p-3 shadow-sm">
                <p class="flex items-center gap-1.5 text-sm font-medium text-slate-700"><x-icono name="escudo-check" class="h-4 w-4" /> Asistentes</p>
                <p class="mt-1"><span data-presentes class="text-2xl font-bold">{{ $presentes }}</span> <span class="text-sm text-slate-500">de {{ $total }}</span></p>
            </div>
            <div class="flex items-center gap-2.5 rounded-2xl border border-aldea-100/80 bg-white p-3 shadow-sm">
                <span id="red-punto" class="h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-500"></span>
                <div class="min-w-0 leading-tight">
                    <p id="red-titulo" class="text-sm font-semibold">En línea</p>
                    <p id="red-detalle" class="text-xs text-slate-500">Registro al momento</p>
                </div>
            </div>
        </section>

        {{-- Cámara --}}
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-b from-aldea-900 to-[#160626] shadow-lg">
            <div id="lector" class="aspect-[4/5] w-full sm:aspect-square"></div>

            <div class="pointer-events-none absolute inset-0 flex flex-col">
                <div class="p-4">
                    <span id="camara-estado" class="inline-flex items-center gap-1.5 rounded-full bg-black/40 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">
                        <span class="h-2 w-2 rounded-full bg-slate-400"></span> Cámara apagada
                    </span>
                </div>
                <div class="relative mx-auto aspect-square w-3/5">
                    <span class="absolute left-0 top-0 h-8 w-8 rounded-tl-lg border-l-4 border-t-4 border-aldea-300"></span>
                    <span class="absolute right-0 top-0 h-8 w-8 rounded-tr-lg border-r-4 border-t-4 border-aldea-300"></span>
                    <span class="absolute bottom-0 left-0 h-8 w-8 rounded-bl-lg border-b-4 border-l-4 border-aldea-300"></span>
                    <span class="absolute bottom-0 right-0 h-8 w-8 rounded-br-lg border-b-4 border-r-4 border-aldea-300"></span>
                    <span id="camara-icono" class="absolute inset-0 grid place-items-center text-white/40"><x-icono name="qr" class="h-12 w-12" /></span>
                    <span id="camara-linea" hidden class="absolute inset-x-3 top-1/2 h-0.5 animate-pulse bg-aldea-300 shadow-[0_0_12px_2px] shadow-aldea-300/60"></span>
                </div>
                <div class="mt-auto bg-gradient-to-t from-black/60 to-transparent px-4 pb-4 pt-8 text-center">
                    <p class="text-sm font-medium text-white/90">Apunta al código QR que el alumno muestra en «Mis pases».</p>
                    <button type="button" id="btn-camara"
                            class="pointer-events-auto mt-3 inline-flex items-center gap-2 rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-aldea-900 shadow hover:bg-aldea-50">
                        <x-icono name="qr" class="h-4 w-4" /> <span data-texto>Activar cámara</span>
                    </button>
                </div>
            </div>
        </section>

        {{-- Resultado del último escaneo --}}
        <section id="resultado-wrap" hidden class="mt-4">
            <div id="resultado" class="rounded-2xl p-4" role="status" aria-live="polite"></div>
        </section>

        {{-- Cola sin conexión --}}
        <section id="pendientes" hidden class="mt-4 rounded-2xl bg-aldea-50 p-4">
            <div class="flex items-center justify-between gap-2">
                <h2 class="flex items-center gap-2 font-semibold"><x-icono name="nube" class="h-5 w-5 text-aldea-700" /> Pendientes de enviar</h2>
                <span data-contador class="rounded-full bg-aldea-100 px-2.5 py-1 text-xs font-semibold text-aldea-800"></span>
            </div>
            <p class="mt-1 text-sm text-slate-600">Escaneos guardados en este dispositivo sin Internet. Se envían solos al recuperar la conexión; no cierres sesión.</p>
            <button type="button" id="btn-sincronizar"
                    class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl bg-aldea-800 py-3 text-[15px] font-semibold text-white hover:bg-aldea-700 disabled:opacity-60">
                <x-icono name="sincronizar" class="h-5 w-5" /> Enviar ahora (Sincronizar)
            </button>
        </section>

        {{-- Lista de alumnos --}}
        <section class="mt-6">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold">Alumnos inscritos</h2>
                <span class="text-sm text-slate-500">{{ $total }}</span>
            </div>

            @if ($total > 0)
                <label class="relative mb-3 block">
                    <span class="sr-only">Buscar alumno</span>
                    <x-icono name="buscar" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input type="search" id="buscar-alumno" placeholder="Buscar por nombre o matrícula"
                           class="w-full rounded-xl border-aldea-100 py-2.5 pl-10 text-sm focus:border-aldea-500 focus:ring-aldea-500">
                </label>
            @endif

            <ul class="space-y-2" id="lista-alumnos">
                @forelse ($inscritos as $e)
                    <li data-enrollment="{{ $e->id }}" data-buscar="{{ mb_strtolower($e->user->name.' '.$e->user->matricula) }}"
                        class="flex items-center gap-3 rounded-xl border border-aldea-100/80 bg-white px-3 py-2.5">
                        <x-iniciales :name="$e->user->name" size="h-9 w-9 text-xs" />
                        <div class="min-w-0 flex-1 leading-tight">
                            <p class="truncate text-sm font-semibold">{{ $e->user->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $e->user->matricula ?? 'Sin matrícula' }}</p>
                        </div>
                        <div data-estado class="shrink-0">
                            @if ($e->attended)
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">✓ {{ $e->checkin_time?->format('H:i') }}</span>
                            @else
                                <form method="POST" action="{{ route('pase-lista.manual', [$activity, $e->id]) }}"
                                      onsubmit="return confirm('¿Registrar manualmente la asistencia de {{ addslashes($e->user->name) }}?')">
                                    @csrf
                                    <button type="submit" class="rounded-lg border border-aldea-200 px-2.5 py-1 text-xs font-semibold text-aldea-800 hover:bg-aldea-50">
                                        Registrar
                                    </button>
                                </form>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="rounded-2xl bg-aldea-50 px-6 py-8 text-center text-sm text-slate-600">Todavía no hay alumnos inscritos en esta actividad.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-staff-layout>
