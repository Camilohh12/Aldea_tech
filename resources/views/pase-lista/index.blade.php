<x-staff-layout title="Pase de lista">
    <section class="mb-5 rounded-2xl bg-gradient-to-b from-aldea-100/80 to-aldea-50 p-4">
        <h1 class="flex items-center gap-2 text-xl font-bold"><x-icono name="qr" class="h-6 w-6 text-aldea-700" /> Pase de lista</h1>
        <p class="mt-1 text-sm text-slate-600">Elige la actividad, abre el pase de lista y escanea el código QR de cada alumno al entrar.</p>
    </section>

    @if ($hoy->isNotEmpty())
        <h2 class="mb-3 flex items-center gap-2 text-lg font-semibold text-aldea-900">
            <span class="h-2 w-2 rounded-full bg-aldea-800"></span> Hoy
        </h2>
        <div class="mb-6 space-y-3">
            @foreach ($hoy as $activity)
                @include('pase-lista._tarjeta', ['activity' => $activity])
            @endforeach
        </div>
    @endif

    @forelse ($porDia as $dia => $activities)
        <h2 class="mb-3 flex items-center gap-2 text-lg font-semibold text-slate-800">
            <span class="h-2 w-2 rounded-full bg-slate-300"></span>
            {{ $dia === 'sin-fecha' ? 'Fecha por definir' : \Illuminate\Support\Str::ucfirst(\Illuminate\Support\Carbon::parse($dia)->locale('es')->isoFormat('dddd, D [de] MMMM')) }}
        </h2>
        <div class="mb-6 space-y-3">
            @foreach ($activities as $activity)
                @include('pase-lista._tarjeta', ['activity' => $activity])
            @endforeach
        </div>
    @empty
        @if ($hoy->isEmpty())
            <div class="rounded-2xl bg-aldea-50 px-6 py-10 text-center">
                <p class="text-lg font-semibold">No hay actividades registradas</p>
                <p class="mt-1 text-sm text-slate-600">Cuando el administrador cargue el programa, aparecerán aquí.</p>
            </div>
        @endif
    @endforelse
</x-staff-layout>
