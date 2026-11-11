<x-tutor-layout title="Supervisión de tutorados">
    @php
        $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 1), '0'), '.');
        $filtro = fn (?string $valor) => route('tutor.tutorados', array_filter(['q' => $q, 'estado' => $valor]));
        $chips = ['todos' => 'Todos'] + \App\Support\ProgresoTutorado::ESTADOS;
        $iniciales = fn (string $nombre) => collect(preg_split('/\s+/', trim($nombre)))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
    @endphp

    <!-- Encabezado -->
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-upp-secondary">
                Coordinación de Tutorías · <span class="text-upp-primary">Aldea Tecnológica 2026</span>
            </p>
            <h1 class="mt-2 font-heading text-2xl font-bold text-upp-primary sm:text-3xl">Supervisión y Seguimiento de Tutorados</h1>
            <p class="mt-1 max-w-3xl text-sm text-gray-600">
                Consulta las actividades, asistencias validadas y horas acumuladas de tus tutorados para la liberación curricular
                (meta: {{ $fmt($meta) }} horas).
            </p>
        </div>

        <!-- Reporte en PDF con la búsqueda y el estado elegidos -->
        <a href="{{ route('tutor.tutorados.reporte', array_filter(['q' => $q, 'estado' => $estado])) }}" data-resultados="reporte"
           class="inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-upp-primary-container px-5 py-3 text-sm font-medium text-white shadow-sm transition hover:bg-upp-primary-dark">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M5 20h14" /></svg>
            Descargar reporte (PDF)
        </a>
    </div>

    <!-- Indicadores -->
    @php
        $tarjetas = [
            ['Tutorados asignados', $indicadores['asignados'], 'Alumnos a tu cargo', 'border-upp-primary-container', 'M17 20h5v-2a3 3 0 00-5.4-1.9M17 20H7m10 0v-2c0-.7-.1-1.3-.4-1.9M7 20H2v-2a3 3 0 015.4-1.9M7 20v-2c0-.7.1-1.3.4-1.9m0 0a5 5 0 019.2 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
            ['Asistencias validadas', $indicadores['asistencias'], 'Registradas por los responsables', 'border-emerald-500', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['Alumnos pendientes', $indicadores['pendientes'], 'Aún sin horas acreditadas', 'border-red-500', 'M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z'],
            ['Inscripciones activas', $indicadores['inscripciones'], 'Talleres y conferencias', 'border-upp-secondary', 'M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z'],
        ];
    @endphp
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
        @foreach ($tarjetas as [$titulo, $valor, $nota, $borde, $icono])
            <div class="rounded-xl border-l-4 {{ $borde }} bg-white p-4 shadow-sm lg:p-5">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-600 lg:text-xs">{{ $titulo }}</p>
                    <span class="hidden rounded-lg bg-upp-surface-high p-2 text-upp-primary-container sm:block">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icono }}" /></svg>
                    </span>
                </div>
                <p class="mt-2 font-heading text-3xl font-bold text-upp-primary lg:text-4xl">{{ $valor }}</p>
                <p class="mt-1 text-xs text-upp-secondary lg:text-sm">{{ $nota }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Tabla de tutorados -->
        <section class="overflow-hidden rounded-xl bg-white shadow-sm lg:col-span-2">
            <div class="border-b border-upp-surface-high px-4 py-4 lg:px-6">
                <h2 class="font-heading text-lg font-bold text-upp-primary">Padrón de estudiantes bajo tutoría</h2>
                <p class="text-sm text-gray-500">Avance en actividades extracurriculares y horas acreditadas.</p>

                <form method="GET" action="{{ route('tutor.tutorados') }}" data-filtro-vivo class="mt-4 flex flex-col gap-3 md:flex-row md:items-center">
                    @if ($estado)<input type="hidden" name="estado" value="{{ $estado }}">@endif
                    <div class="relative md:w-72">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" /></svg>
                        <input type="search" name="q" value="{{ $q }}" placeholder="Buscar por nombre o matrícula..."
                               class="w-full rounded-lg border-gray-300 py-2 pl-9 text-sm focus:border-upp-primary-container focus:ring-upp-primary-container">
                    </div>
                    <div class="-mx-1 flex gap-1.5 overflow-x-auto px-1 pb-1 md:pb-0" data-resultados="estados">
                        @foreach ($chips as $clave => $etiqueta)
                            @php $activo = ($estado ?? 'todos') === $clave; @endphp
                            <a href="{{ $filtro($clave === 'todos' ? null : $clave) }}"
                               class="whitespace-nowrap rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $activo ? 'bg-upp-primary text-white' : 'bg-upp-surface-low text-upp-secondary hover:bg-upp-surface-high' }}">
                                {{ $etiqueta }} ({{ $conteos[$clave] }})
                            </a>
                        @endforeach
                    </div>
                </form>
            </div>

            <div data-resultados="tutorados">
            @if ($tutorados->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="font-heading font-bold text-upp-primary">
                        {{ $conteos['todos'] === 0 ? 'Aún no tienes tutorados asignados' : 'Sin resultados' }}
                    </p>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ $conteos['todos'] === 0 ? 'Cuando la coordinación te asigne alumnos aparecerán aquí.' : 'Prueba con otro nombre, matrícula o estado.' }}
                    </p>
                </div>
            @else
                <!-- Escritorio: tabla -->
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-upp-surface-low text-xs uppercase tracking-wider text-upp-secondary">
                            <tr>
                                <th class="px-4 py-3 font-semibold lg:px-6">Estudiante</th>
                                <th class="px-3 py-3 font-semibold">Actividad</th>
                                <th class="px-3 py-3 font-semibold">Horas</th>
                                <th class="px-3 py-3 font-semibold">Estado</th>
                                <th class="px-3 py-3 text-right font-semibold lg:pr-6">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-upp-surface-high">
                            @foreach ($tutorados as $p)
                                <tr class="hover:bg-upp-surface-low/60">
                                    <td class="px-4 py-3 lg:px-6">
                                        <div class="flex items-center gap-3">
                                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-upp-surface-high font-heading text-xs font-bold text-upp-primary-container">{{ $iniciales($p->alumno->name) }}</span>
                                            <div class="min-w-0">
                                                <p class="font-semibold leading-tight">{{ $p->alumno->name }}</p>
                                                <p class="text-xs text-gray-500">Matrícula {{ $p->alumno->matricula ?? '—' }}</p>
                                                <p class="max-w-[12rem] truncate text-xs text-upp-secondary" title="{{ $p->alumno->carrera }}">{{ $p->alumno->carrera ?? 'Sin carrera' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="max-w-[11rem] px-3 py-3">
                                        @if ($p->actividadDestacada)
                                            <p class="truncate font-medium">{{ $p->actividadDestacada->title }}</p>
                                            <p class="text-xs text-gray-500">{{ $p->actividadDestacada->activityType?->name ?? 'Actividad' }} · {{ \App\Models\Activity::shortDate($p->actividadDestacada->date) }}</p>
                                        @else
                                            <p class="text-xs text-gray-400">Sin inscripciones</p>
                                        @endif
                                    </td>
                                    <td class="w-24 px-3 py-3">
                                        <p class="whitespace-nowrap text-sm font-semibold text-upp-primary">{{ $fmt($p->horas) }} / {{ $fmt($p->meta) }}</p>
                                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-upp-surface-high">
                                            <div class="h-full rounded-full {{ $p->estado === 'acreditado' ? 'bg-emerald-500' : 'bg-upp-primary-container' }}" style="width: {{ $p->porcentaje }}%"></div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3">@include('tutor._estado', ['progreso' => $p])</td>
                                    <td class="px-3 py-3 text-right lg:pr-6">
                                        <a href="{{ route('tutor.tutorados.show', $p->alumno) }}"
                                           class="inline-flex rounded-lg border border-upp-surface-high px-3 py-1.5 text-xs font-semibold text-upp-primary-container hover:bg-upp-surface-low">
                                            Consultar
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Móvil: tarjetas -->
                <ul class="divide-y divide-upp-surface-high md:hidden">
                    @foreach ($tutorados as $p)
                        <li class="px-4 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-upp-surface-high font-heading text-xs font-bold text-upp-primary-container">{{ $iniciales($p->alumno->name) }}</span>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold leading-tight">{{ $p->alumno->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $p->alumno->matricula ?? '—' }}</p>
                                    </div>
                                </div>
                                @include('tutor._estado', ['progreso' => $p])
                            </div>
                            <div class="mt-3 flex items-center justify-between text-xs">
                                <span class="text-gray-500">Horas acreditadas</span>
                                <span class="font-semibold text-upp-primary">{{ $fmt($p->horas) }} / {{ $fmt($p->meta) }}</span>
                            </div>
                            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-upp-surface-high">
                                <div class="h-full rounded-full {{ $p->estado === 'acreditado' ? 'bg-emerald-500' : 'bg-upp-primary-container' }}" style="width: {{ $p->porcentaje }}%"></div>
                            </div>
                            <a href="{{ route('tutor.tutorados.show', $p->alumno) }}"
                               class="mt-3 block rounded-lg bg-upp-surface-low py-2 text-center text-sm font-semibold text-upp-primary-container">
                                Consultar
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            <p class="border-t border-upp-surface-high px-4 py-3 text-xs text-gray-500 lg:px-6">
                Mostrando {{ $tutorados->count() }} de {{ $conteos['todos'] }} tutorados
            </p>
            </div>
        </section>

        <!-- Columna lateral -->
        <aside class="space-y-6">
            <section class="rounded-xl bg-white p-5 shadow-sm">
                <h2 class="font-heading text-base font-bold text-upp-primary">Resumen de horas por carrera</h2>
                <p class="text-xs text-gray-500">Horas acumuladas de tus tutorados contra la meta del ciclo.</p>

                <div class="mt-4 space-y-4">
                    @forelse ($porCarrera as $c)
                        <div>
                            <div class="flex items-start justify-between gap-3 text-xs">
                                <p class="font-semibold text-[#1C1B1F]">{{ $c['carrera'] }} <span class="font-normal text-gray-500">({{ $c['alumnos'] }} {{ $c['alumnos'] === 1 ? 'alumno' : 'alumnos' }})</span></p>
                                <p class="shrink-0 font-semibold text-upp-primary">{{ $fmt($c['horas']) }} / {{ $fmt($c['meta']) }} hrs</p>
                            </div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-upp-surface-high">
                                <div class="h-full rounded-full bg-upp-primary-container" style="width: {{ $c['porcentaje'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Sin datos todavía.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-xl bg-white p-5 shadow-sm">
                <h2 class="font-heading text-base font-bold text-upp-primary">Próximas actividades</h2>
                <p class="text-xs text-gray-500">Donde hay tutorados tuyos inscritos.</p>

                <ol class="mt-4 space-y-4">
                    @forelse ($proximas as $i => $item)
                        @php $a = $item['actividad']; @endphp
                        <li class="flex gap-3">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full {{ $i === 0 ? 'bg-upp-primary text-white' : 'bg-upp-surface-high text-upp-primary-container' }} text-xs font-bold">{{ $i + 1 }}</span>
                            <div class="min-w-0">
                                <p class="font-semibold leading-snug">{{ $a->title }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ \Illuminate\Support\Str::ucfirst($a->date?->locale('es')->isoFormat('dddd D [de] MMM') ?? 'Fecha por definir') }} · {{ $a->timeRange() }} h
                                </p>
                                <p class="text-xs text-upp-secondary">{{ $a->room?->name ?? 'Sede por confirmar' }} · {{ $item['tutorados'] }} {{ $item['tutorados'] === 1 ? 'tutorado' : 'tutorados' }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-gray-500">Tus tutorados no tienen actividades próximas.</li>
                    @endforelse
                </ol>
            </section>
        </aside>
    </div>
</x-tutor-layout>
