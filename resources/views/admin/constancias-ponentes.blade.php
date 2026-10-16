<x-staff-layout title="Constancias de ponentes" section="Constancias de ponentes">

    <section class="mb-5 rounded-2xl bg-gradient-to-b from-aldea-100/80 to-aldea-50 p-4">
        <p class="flex items-center gap-2 text-lg font-semibold text-aldea-900"><x-icono name="diploma" class="h-5 w-5" /> Constancias de ponentes</p>
        <p class="mt-1 text-sm text-slate-600">Genera en PDF la constancia de cada ponente. Se habilita cuando termina su actividad.</p>
        <div class="mt-3 grid grid-cols-2 gap-3">
            <div class="rounded-xl bg-white p-3">
                <p class="text-sm font-medium text-slate-600">Listas</p>
                <p class="mt-0.5 text-2xl font-bold">{{ $listas->count() }}</p>
            </div>
            <div class="rounded-xl bg-white p-3">
                <p class="text-sm font-medium text-slate-600">Por realizarse</p>
                <p class="mt-0.5 text-2xl font-bold">{{ $proximas->count() }}</p>
            </div>
        </div>
    </section>

    <form method="GET" class="mb-5">
        <label class="relative block">
            <span class="sr-only">Buscar</span>
            <x-icono name="buscar" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input type="search" name="q" value="{{ $q }}" placeholder="Buscar por actividad o ponente"
                   class="w-full rounded-xl border-aldea-100 bg-white py-2.5 pl-10 text-sm shadow-sm focus:border-aldea-500 focus:ring-aldea-500">
        </label>
    </form>

    {{-- Listas para generar --}}
    <h2 class="mb-3 flex items-center gap-2 text-lg font-semibold">
        <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Listas para generar
    </h2>

    @forelse ($listas as $a)
        <article class="mb-3 rounded-2xl border border-aldea-100/80 bg-white p-4 shadow-sm">
            <div class="flex items-start gap-3">
                <x-iniciales :name="$a->speaker->name" size="h-10 w-10 text-xs" />
                <div class="min-w-0 flex-1">
                    <p class="font-semibold leading-tight">{{ $a->speaker->name }}</p>
                    <p class="text-xs text-slate-500">{{ $a->speaker->bio ? \Illuminate\Support\Str::limit(strtok($a->speaker->bio, "\n"), 50) : 'Ponente' }}</p>
                </div>
            </div>
            <p class="mt-3 text-xs font-semibold text-aldea-700">
                {{ $a->type?->name ?? 'Actividad' }} · {{ \App\Models\Activity::shortDate($a->date) ?? 'Sin fecha' }} · {{ $a->timeRange() }} hrs
            </p>
            <h3 class="mt-0.5 font-semibold leading-snug">{{ $a->title }}</h3>
            <p class="mt-1 text-sm text-slate-600">{{ $a->room?->name ?? 'Sede por confirmar' }} · {{ $a->asistentes }} {{ $a->asistentes === 1 ? 'asistente' : 'asistentes' }}</p>

            <div class="mt-3 flex items-center justify-between gap-3 border-t border-slate-100 pt-3">
                <p class="font-mono text-xs text-slate-500">Folio {{ $a->speakerCertificateFolio() }}</p>
                <a href="{{ route('admin.constancias.pdf', $a) }}"
                   class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-aldea-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-aldea-700">
                    <x-icono name="descargar" class="h-4 w-4" /> Generar PDF
                </a>
            </div>
        </article>
    @empty
        <div class="mb-3 rounded-2xl bg-aldea-50 px-6 py-8 text-center">
            <span class="mx-auto grid h-11 w-11 place-items-center rounded-xl bg-aldea-100 text-aldea-700"><x-icono name="diploma" class="h-5 w-5" /></span>
            <p class="mt-3 font-semibold">{{ $q !== '' ? 'Sin resultados' : 'Todavía no hay constancias por generar' }}</p>
            <p class="mt-1 text-sm text-slate-600">Aparecen aquí cuando termina cada actividad con ponente asignado.</p>
        </div>
    @endforelse

    {{-- Próximas --}}
    @if ($proximas->isNotEmpty())
        <h2 class="mb-3 mt-7 flex items-center gap-2 text-lg font-semibold text-slate-700">
            <span class="h-2 w-2 rounded-full bg-slate-300"></span> Por realizarse
        </h2>
        @foreach ($proximas as $a)
            <article class="mb-3 flex items-center justify-between gap-3 rounded-2xl border border-slate-200/80 bg-white/70 p-4">
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-slate-500">{{ \App\Models\Activity::shortDate($a->date) ?? 'Sin fecha' }} · {{ $a->timeRange() }} hrs</p>
                    <h3 class="truncate font-semibold text-slate-700">{{ $a->title }}</h3>
                    <p class="mt-0.5 truncate text-xs text-slate-500">{{ $a->speaker->name }}</p>
                </div>
                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-400">
                    <x-icono name="candado" class="h-4 w-4" /> Bloqueada
                </span>
            </article>
        @endforeach
    @endif

    {{-- Sin ponente --}}
    @if ($sinPonente->isNotEmpty())
        <h2 class="mb-3 mt-7 flex items-center gap-2 text-lg font-semibold text-amber-700">
            <span class="h-2 w-2 rounded-full bg-amber-400"></span> Sin ponente asignado
        </h2>
        @foreach ($sinPonente as $a)
            <article class="mb-3 rounded-2xl border border-amber-200 bg-amber-50/60 p-4">
                <p class="text-xs font-semibold text-amber-700">{{ \App\Models\Activity::shortDate($a->date) ?? 'Sin fecha' }} · {{ $a->timeRange() }} hrs</p>
                <h3 class="font-semibold text-slate-700">{{ $a->title }}</h3>
                <p class="mt-0.5 text-xs text-slate-600">Asigna un ponente a la actividad para poder generar su constancia.</p>
            </article>
        @endforeach
    @endif
</x-staff-layout>
