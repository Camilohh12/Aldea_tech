<x-alumno-layout title="Escanear asistencia" section="Escanear">
    <x-slot name="head">
        @vite('resources/js/escaner.js')
    </x-slot>

    <div id="escaner-app"
         data-store-url="{{ route('alumno.asistencias.store') }}"
         data-sync-url="{{ route('alumno.asistencias.sync') }}"
         data-user-id="{{ auth()->id() }}">

        {{-- Datos para mostrar nombres de actividades sin conexión (hash del código, nunca el código) --}}
        <script type="application/json" id="catalogo-offline">@json($catalogoOffline, JSON_UNESCAPED_UNICODE)</script>

        {{-- Estado de conexión --}}
        <section class="mb-4 flex items-center gap-3 rounded-2xl border border-aldea-100 bg-white px-4 py-3 shadow-sm">
            <span id="red-punto" class="h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-500"></span>
            <div class="min-w-0 flex-1 leading-tight">
                <p id="red-titulo" class="truncate text-sm font-semibold">En línea</p>
                <p id="red-detalle" class="truncate text-xs text-slate-500">Servidor UPP disponible</p>
            </div>
            <span id="red-pill" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-aldea-100 bg-aldea-50 px-2.5 py-1.5 text-xs font-semibold text-aldea-800">
                <x-icono name="wifi" class="h-4 w-4" /> <span data-texto>Conectado</span>
            </span>
        </section>

        {{-- Cámara --}}
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-b from-aldea-900 to-[#160626] shadow-lg">
            <div id="lector" class="aspect-[4/5] w-full sm:aspect-square"></div>

            {{-- Capa visual (no bloquea la cámara) --}}
            <div class="pointer-events-none absolute inset-0 flex flex-col">
                <div class="flex items-start justify-between p-4">
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
                    <p class="text-sm font-medium text-white/90">Apunta al código QR institucional que está en el aula o que muestra el moderador UPP.</p>
                    <button type="button" id="btn-camara"
                            class="pointer-events-auto mt-3 inline-flex items-center gap-2 rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-aldea-900 shadow hover:bg-aldea-50">
                        <x-icono name="qr" class="h-4 w-4" /> <span data-texto>Activar cámara</span>
                    </button>
                </div>
            </div>
        </section>

        {{-- Resultado del último escaneo --}}
        <section id="resultado-wrap" hidden class="mt-6">
            <h2 class="mb-2 text-xs font-bold uppercase tracking-[0.12em] text-slate-600">Último registro</h2>
            <div id="resultado" class="rounded-2xl p-4" role="status" aria-live="polite"></div>
        </section>

        {{-- Cola sin conexión --}}
        <section id="pendientes" hidden class="mt-6 rounded-2xl bg-aldea-50 p-4">
            <div class="flex items-center justify-between gap-2">
                <h2 class="flex items-center gap-2 text-lg font-semibold"><x-icono name="nube" class="h-5 w-5 text-aldea-700" /> Pendientes de enviar</h2>
                <span data-contador class="rounded-full bg-aldea-100 px-2.5 py-1 text-xs font-semibold text-aldea-800"></span>
            </div>
            <p class="mt-1 text-sm text-slate-600">Registros guardados en este teléfono porque no había Internet. Se envían solos al recuperar la conexión.</p>
            <ul data-lista class="mt-3 space-y-2"></ul>
            <button type="button" id="btn-sincronizar"
                    class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-aldea-800 py-3 text-[15px] font-semibold text-white shadow-sm hover:bg-aldea-700 disabled:opacity-60">
                <x-icono name="sincronizar" class="h-5 w-5" /> Enviar ahora (Sincronizar)
            </button>
        </section>

        {{-- Captura manual --}}
        <details class="group mt-6 rounded-2xl bg-aldea-50 px-4 py-3.5">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-2 text-[15px] font-semibold text-aldea-900">
                <span class="flex items-center gap-2"><x-icono name="teclado" class="h-5 w-5" /> ¿Problemas con el QR? Registro manual</span>
                <svg class="h-4 w-4 transition group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </summary>
            <form id="form-manual" class="mt-3 flex gap-2">
                <input type="text" name="codigo" required maxlength="64" autocomplete="off" placeholder="Código del aula"
                       class="min-w-0 flex-1 rounded-xl border-aldea-100 text-sm focus:border-aldea-500 focus:ring-aldea-500">
                <button type="submit" class="rounded-xl bg-aldea-800 px-4 text-sm font-semibold text-white hover:bg-aldea-700">Registrar</button>
            </form>
        </details>
    </div>
</x-alumno-layout>
