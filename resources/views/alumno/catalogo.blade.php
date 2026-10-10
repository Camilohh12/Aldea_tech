@php
    $user = auth()->user();
    $chip = fn (bool $on) => $on
        ? 'whitespace-nowrap rounded-full bg-aldea-800 px-4 py-2 text-sm font-semibold text-white shadow-sm'
        : 'whitespace-nowrap rounded-full border border-aldea-100 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:border-aldea-300';
    $chipTipo = fn (bool $on) => $on
        ? 'whitespace-nowrap rounded-md bg-aldea-800 px-3 py-1 text-xs font-semibold text-white'
        : 'whitespace-nowrap rounded-md px-3 py-1 text-xs font-medium text-slate-700 hover:bg-aldea-50';
    $url = fn (array $cambios) => route('alumno.catalogo', array_filter(array_merge($filters, $cambios)));
@endphp

<x-alumno-layout title="Actividades" section="Actividades" wide>

    {{-- Tarjeta del alumno --}}
    <section class="mb-4 flex items-center gap-3 rounded-2xl border border-aldea-100 bg-white p-4 shadow-sm">
        <x-iniciales :name="$user->name" size="h-12 w-12 text-base" class="rounded-xl" />
        <div class="min-w-0 flex-1">
            <p class="truncate text-lg font-bold">{{ $user->name }}</p>
            <p class="truncate text-sm text-slate-600">{{ $user->matricula ?? 'Sin matrícula' }} • {{ $user->carrera ?? 'Carrera no registrada' }}</p>
        </div>
        <span class="shrink-0 rounded-full border border-aldea-100 bg-aldea-50 px-3 py-1 text-xs font-semibold text-aldea-800">● Alumno activo</span>
    </section>

    {{-- Buscador y filtros --}}
    <form method="GET" action="{{ route('alumno.catalogo') }}" class="mb-4 space-y-3">
        @if (! empty($filters['tipo']))<input type="hidden" name="tipo" value="{{ $filters['tipo'] }}">@endif
        @if (! empty($filters['fecha']))<input type="hidden" name="fecha" value="{{ $filters['fecha'] }}">@endif

        <label class="relative block">
            <span class="sr-only">Buscar</span>
            <x-icono name="buscar" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-500" />
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Buscar por tema o ponente..."
                   class="w-full rounded-2xl border-aldea-100 bg-white py-3 pl-11 text-[15px] shadow-sm placeholder:text-slate-500 focus:border-aldea-500 focus:ring-aldea-500">
        </label>

        <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1">
            <a href="{{ $url(['fecha' => null]) }}" class="{{ $chip(empty($filters['fecha'])) }}">Todos los días</a>
            @foreach ($dates as $i => $date)
                <a href="{{ $url(['fecha' => $date]) }}" class="{{ $chip(($filters['fecha'] ?? null) === $date) }}">
                    Día {{ $i + 1 }} ({{ \App\Models\Activity::shortDate($date) }})
                </a>
            @endforeach
        </div>

        <div class="-mx-4 flex gap-1.5 overflow-x-auto px-4">
            <a href="{{ $url(['tipo' => null]) }}" class="{{ $chipTipo(empty($filters['tipo'])) }}">Todos</a>
            @foreach ($types as $type)
                <a href="{{ $url(['tipo' => $type->id]) }}" class="{{ $chipTipo(($filters['tipo'] ?? null) == $type->id) }}">{{ $type->name }}</a>
            @endforeach
        </div>
    </form>

    {{-- Tarjetas --}}
    <div class="grid gap-4 md:grid-cols-2">
        @forelse ($activities as $activity)
            @php
                $enrolled = $misInscripciones->has($activity->id);
                $attended = (bool) $misInscripciones->get($activity->id);
                $capacity = $activity->effectiveCapacity();
                $available = $activity->availableSpots();
                $full = $activity->isFull() && ! $enrolled;
                $started = $activity->hasStarted();
                $registered = $activity->registeredCount();
                $percent = $capacity ? min(100, round($registered / $capacity * 100)) : 0;
                $bio = $activity->speaker?->bio ? \Illuminate\Support\Str::limit(strtok($activity->speaker->bio, "\n"), 40) : 'Ponente';
            @endphp

            <article class="flex flex-col rounded-2xl border border-aldea-100/80 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between gap-2">
                    @if ($activity->type)
                        <span @class([
                            'rounded-full px-2.5 py-1 text-xs font-semibold',
                            'bg-aldea-200/70 text-aldea-800' => $enrolled,
                            'bg-aldea-100 text-aldea-800' => ! $enrolled,
                        ])>{{ $activity->type->name }}</span>
                    @else
                        <span></span>
                    @endif

                    @if ($enrolled)
                        <span class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                            <x-icono name="check" class="h-3.5 w-3.5" /> Inscrito
                        </span>
                    @elseif ($full)
                        <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-600">
                            <x-icono name="prohibido" class="h-3.5 w-3.5" /> Cupo lleno
                        </span>
                    @elseif ($started)
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">Cerrada</span>
                    @else
                        <span class="inline-flex items-center gap-1 text-[13px] font-medium text-slate-700">
                            <x-icono name="reloj" class="h-4 w-4" />
                            {{ \App\Models\Activity::shortDate($activity->date) ?? '' }} · {{ $activity->timeRange() }} hrs
                        </span>
                    @endif
                </div>

                @if ($enrolled || $full || $started)
                    <p class="mt-2 inline-flex items-center gap-1 text-[13px] font-medium text-slate-700">
                        <x-icono name="reloj" class="h-4 w-4" />
                        {{ \App\Models\Activity::shortDate($activity->date) ?? 'Fecha por definir' }} · {{ $activity->timeRange() }} hrs
                    </p>
                @endif

                <h2 class="mt-2 text-lg font-semibold leading-snug">{{ $activity->title }}</h2>

                @if ($activity->description)
                    <p class="mt-1 line-clamp-2 text-sm leading-relaxed text-slate-600">{{ $activity->description }}</p>
                @endif

                <div class="mt-3 flex items-center gap-2.5">
                    <x-iniciales :name="$activity->speaker?->name" size="h-8 w-8 text-[11px]" />
                    <div class="min-w-0 leading-tight">
                        <p class="truncate text-sm font-semibold">{{ $activity->speaker?->name ?? 'Ponente por confirmar' }}</p>
                        <p class="truncate text-xs font-medium text-slate-600">{{ $bio }}</p>
                    </div>
                </div>

                <p class="mt-2 flex items-center gap-1.5 text-sm text-slate-600">
                    <x-icono name="ubicacion" class="h-4 w-4 text-aldea-700" />
                    {{ $activity->room?->name ?? 'Sede por confirmar' }}
                </p>

                <div class="flex-1"></div>

                {{-- Cupo --}}
                <div class="mt-4">
                    <div class="flex items-center justify-between gap-2 text-[13px]">
                        <span class="font-medium text-slate-700">
                            @if ($attended) Asistencia registrada
                            @elseif ($enrolled) Lugar confirmado
                            @elseif ($full) Capacidad agotada
                            @else Capacidad de sala
                            @endif
                        </span>
                        <span @class(['font-semibold', 'text-red-600' => $full, 'text-aldea-800' => ! $full])>
                            @if ($capacity)
                                {{ $registered }}/{{ $capacity }} · {{ $available }} {{ $available === 1 ? 'lugar disponible' : 'lugares disponibles' }}
                            @else
                                Cupo abierto
                            @endif
                        </span>
                    </div>
                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-aldea-100">
                        <div @class([
                            'h-full rounded-full',
                            'bg-red-600' => $full,
                            'bg-aldea-500' => $enrolled,
                            'bg-aldea-800' => ! $full && ! $enrolled,
                        ]) style="width: {{ $capacity ? $percent : 0 }}%"></div>
                    </div>
                </div>

                {{-- Acción --}}
                <div class="mt-3">
                    @if ($enrolled)
                        <a href="{{ route('alumno.agenda') }}" class="flex w-full items-center justify-center gap-2 rounded-xl bg-aldea-50 py-3 text-[15px] font-semibold text-aldea-800 hover:bg-aldea-100">
                            <x-icono name="calendario" class="h-5 w-5" /> Ver en Mi Agenda
                        </a>
                    @elseif ($full || $started)
                        <span class="flex w-full items-center justify-center gap-2 rounded-xl bg-slate-100 py-3 text-[15px] font-semibold text-slate-400">
                            <x-icono name="candado" class="h-5 w-5" /> {{ $full ? 'Cupo agotado' : 'Inscripción cerrada' }}
                        </span>
                    @else
                        <form method="POST" action="{{ route('alumno.inscripcion.store', $activity) }}" x-data="{ sending: false }" @submit="sending = true">
                            @csrf
                            <button type="submit" :disabled="sending"
                                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-aldea-800 py-3 text-[15px] font-semibold text-white shadow-sm transition hover:bg-aldea-700 disabled:opacity-60">
                                <x-icono name="mas" class="h-5 w-5" />
                                <span x-show="!sending">Inscribirme</span>
                                <span x-show="sending" x-cloak>Inscribiendo…</span>
                            </button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-aldea-200 bg-white px-6 py-12 text-center md:col-span-2">
                <p class="font-semibold">No hay actividades que coincidan</p>
                <p class="mt-1 text-sm text-slate-500">Prueba con otro filtro o vuelve más tarde.</p>
                <a href="{{ route('alumno.catalogo') }}" class="mt-3 inline-block text-sm font-semibold text-aldea-700 hover:underline">Quitar filtros</a>
            </div>
        @endforelse
    </div>
</x-alumno-layout>
