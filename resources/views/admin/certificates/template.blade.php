@extends('layouts.admin')

@section('title', 'Plantilla del certificado')

@section('content')
    {{-- Color institucional: los mismos tonos que pdf/certificate.blade.php --}}
    @php $m = config('aldea.marca'); @endphp

@php
    $field = 'w-full rounded-lg border px-4 py-2.5 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-upp-primary-container';
    $border = fn ($name) => $errors->has($name) ? 'border-red-400' : 'border-gray-300';
    $value = fn ($name) => old($name, $template->{$name});
@endphp

    <!-- Encabezado -->
    <div class="mb-8">

        <a href="{{ route('admin.certificates.index') }}"
           class="inline-flex items-center gap-1 text-sm text-upp-primary-container hover:underline">
            ← Volver a Certificados
        </a>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mt-6">
            <div>
                <p class="text-xs font-semibold tracking-widest uppercase text-upp-secondary">
                    Certificados / <span class="text-upp-primary">Plantilla</span>
                </p>

                <h1 class="font-heading text-3xl sm:text-4xl font-bold text-upp-primary mt-2">
                    Editar certificado
                </h1>

                <p class="text-gray-600 mt-2">
                    Estos textos se usan en todos los certificados de ponentes. El nombre, la actividad y el folio se llenan solos.
                </p>
            </div>

            <a href="{{ route('admin.certificates.sample') }}" target="_blank"
               class="inline-flex items-center justify-center gap-2 shrink-0 whitespace-nowrap px-4 py-2.5 rounded-lg border border-upp-surface-high bg-white text-upp-primary-container font-medium hover:bg-upp-surface-low transition"
               title="Usa la plantilla guardada">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                Ver PDF de muestra
            </a>
        </div>

    </div>

    <div class="grid gap-6 xl:grid-cols-5">

        <!-- Formulario -->
        <form action="{{ route('admin.certificates.template.update') }}" method="POST" id="template-form"
              class="bg-white rounded-xl shadow-sm p-6 xl:col-span-2 space-y-8">

            @csrf
            @method('PUT')

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                    No se guardó la plantilla. Revisa los campos marcados en rojo.
                </div>
            @endif

            <!-- Encabezado del certificado -->
            <section class="space-y-4">
                <h2 class="font-heading font-bold text-upp-primary">Encabezado</h2>

                <div>
                    <label for="institution" class="block text-sm font-semibold text-upp-primary mb-1.5">Institución</label>
                    <input type="text" id="institution" name="institution" maxlength="120" required data-preview-input
                           value="{{ $value('institution') }}" class="{{ $field }} {{ $border('institution') }}">
                    @error('institution') <p class="text-red-600 text-sm mt-1.5">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="event_name" class="block text-sm font-semibold text-upp-primary mb-1.5">Nombre del evento</label>
                    <input type="text" id="event_name" name="event_name" maxlength="120" required data-preview-input
                           value="{{ $value('event_name') }}" class="{{ $field }} {{ $border('event_name') }}">
                    @error('event_name') <p class="text-red-600 text-sm mt-1.5">{{ $message }}</p> @enderror
                </div>
            </section>

            <!-- Título -->
            <section class="space-y-4">
                <h2 class="font-heading font-bold text-upp-primary">Título</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="heading" class="block text-sm font-semibold text-upp-primary mb-1.5">Leyenda superior</label>
                        <input type="text" id="heading" name="heading" maxlength="80" required data-preview-input
                               value="{{ $value('heading') }}" class="{{ $field }} {{ $border('heading') }}">
                        @error('heading') <p class="text-red-600 text-sm mt-1.5">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="title" class="block text-sm font-semibold text-upp-primary mb-1.5">Título</label>
                        <input type="text" id="title" name="title" maxlength="40" required data-preview-input
                               value="{{ $value('title') }}" placeholder="CONSTANCIA o RECONOCIMIENTO"
                               class="{{ $field }} {{ $border('title') }}">
                        @error('title') <p class="text-red-600 text-sm mt-1.5">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <!-- Texto -->
            <section class="space-y-3">
                <h2 class="font-heading font-bold text-upp-primary">Texto del reconocimiento</h2>

                <textarea id="body" name="body" rows="6" maxlength="1000" required data-preview-input
                          class="{{ $field }} {{ $border('body') }} leading-relaxed">{{ $value('body') }}</textarea>
                @error('body') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror

                <div>
                    <p class="text-xs text-gray-500 mb-2">Haz clic en un dato para insertarlo donde está el cursor:</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($variables as $key => $label)
                            <button type="button" data-variable="{{ $key }}" title="{{ $label }}"
                                    class="px-2 py-1 rounded-md bg-upp-surface-high text-upp-primary-container text-xs font-mono hover:bg-upp-secondary-container transition">
                                {{ '{' . $key . '}' }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </section>

            <!-- Firmas -->
            <section class="space-y-4">
                <h2 class="font-heading font-bold text-upp-primary">Firmas</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="signer1_name" class="block text-sm font-semibold text-upp-primary mb-1.5">Firma 1 · Nombre</label>
                        <input type="text" id="signer1_name" name="signer1_name" maxlength="120" required data-preview-input
                               value="{{ $value('signer1_name') }}" class="{{ $field }} {{ $border('signer1_name') }}">
                        @error('signer1_name') <p class="text-red-600 text-sm mt-1.5">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="signer1_title" class="block text-sm font-semibold text-upp-primary mb-1.5">Firma 1 · Cargo</label>
                        <input type="text" id="signer1_title" name="signer1_title" maxlength="120" required data-preview-input
                               value="{{ $value('signer1_title') }}" class="{{ $field }} {{ $border('signer1_title') }}">
                        @error('signer1_title') <p class="text-red-600 text-sm mt-1.5">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="signer2_name" class="block text-sm font-semibold text-upp-primary mb-1.5">
                            Firma 2 · Nombre <span class="font-normal text-gray-500">(opcional)</span>
                        </label>
                        <input type="text" id="signer2_name" name="signer2_name" maxlength="120" data-preview-input
                               value="{{ $value('signer2_name') }}" class="{{ $field }} {{ $border('signer2_name') }}">
                        @error('signer2_name') <p class="text-red-600 text-sm mt-1.5">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="signer2_title" class="block text-sm font-semibold text-upp-primary mb-1.5">
                            Firma 2 · Cargo <span class="font-normal text-gray-500">(opcional)</span>
                        </label>
                        <input type="text" id="signer2_title" name="signer2_title" maxlength="120" data-preview-input
                               value="{{ $value('signer2_title') }}" class="{{ $field }} {{ $border('signer2_title') }}">
                        @error('signer2_title') <p class="text-red-600 text-sm mt-1.5">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <!-- Pie -->
            <section class="space-y-4">
                <h2 class="font-heading font-bold text-upp-primary">Pie del certificado</h2>

                <div>
                    <label for="footer" class="block text-sm font-semibold text-upp-primary mb-1.5">
                        Leyenda al pie <span class="font-normal text-gray-500">(opcional)</span>
                    </label>
                    <input type="text" id="footer" name="footer" maxlength="160" data-preview-input
                           value="{{ $value('footer') }}" placeholder="Ej. Pachuca de Soto, Hidalgo"
                           class="{{ $field }} {{ $border('footer') }}">
                    <p class="text-xs text-gray-500 mt-1.5">Después se agregan solos la fecha de emisión y el folio.</p>
                    @error('footer') <p class="text-red-600 text-sm mt-1.5">{{ $message }}</p> @enderror
                </div>
            </section>

            <!-- Botones -->
            <div class="flex flex-col-reverse sm:flex-row sm:justify-between gap-3 pt-6 border-t border-upp-surface-high">
                <button type="button" id="reset-defaults"
                        class="px-4 py-2.5 rounded-lg text-sm text-gray-600 hover:bg-upp-surface-low transition">
                    Restablecer textos originales
                </button>

                <button type="submit"
                        class="bg-upp-primary-container hover:bg-upp-primary-dark text-white px-6 py-2.5 rounded-lg font-medium shadow-sm transition">
                    Guardar plantilla
                </button>
            </div>

        </form>

        <!-- Vista previa -->
        <div class="xl:col-span-3">
            <div class="xl:sticky xl:top-6">

                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-heading text-lg font-bold text-upp-primary">Vista previa</h2>
                    <p class="text-xs text-gray-500">Ejemplo con {{ $sampleSpeaker->name }}</p>
                </div>

                <!-- Hoja carta horizontal a escala (11 × 8.5) -->
                <div class="bg-white rounded-xl shadow-sm p-3">
                    <div class="relative w-full" style="padding-bottom: 77.27%; container-type: inline-size;">
                        <div class="absolute inset-0 bg-white overflow-hidden" style="font-size: 1.6cqw;">

                            <div class="absolute" style="inset: 3.2%; border: 1.1cqw solid {{ $m[800] }};"></div>
                            <div class="absolute" style="inset: 4.8%; border: 0.15cqw solid {{ $m[300] }};"></div>

                            <div class="absolute text-center" style="top: 9%; left: 9%; right: 9%; bottom: 8%; color: #1b1530;">

                                <div class="flex items-center justify-between">
                                    <img src="{{ asset('img/escudo-upp.png') }}" alt="" class="rounded" style="height: 3.4em; background: {{ $m[900] }}; padding: 0.2em;"
                                         onerror="this.style.visibility='hidden'">
                                    <div class="flex-1 px-2">
                                        <p data-preview="institution" class="font-bold" style="font-size: 0.85em; letter-spacing: 0.22em; color: {{ $m[800] }};"></p>
                                        <p data-preview="event_name" class="font-serif" style="font-size: 1.15em; color: {{ $m[900] }};"></p>
                                    </div>
                                    <div class="text-left" style="border: 0.08em solid {{ $m[200] }}; padding: 0.25em 0.4em; font-size: 0.6em; color: {{ $m[600] }};">
                                        Folio<br><b style="color: {{ $m[900] }};">AT26-P-00000-000-XXXXXX</b>
                                    </div>
                                </div>

                                <p data-preview="heading" style="margin-top: 1.2em; font-size: 0.9em; letter-spacing: 0.1em; color: #475569;"></p>
                                <p data-preview="title" class="font-serif" style="font-size: 3.4em; line-height: 1.1; letter-spacing: 0.22em; color: {{ $m[800] }};"></p>
                                <div style="width: 22%; height: 0.15em; background: #c9a227; margin: 0.3em auto 0;"></div>

                                <p style="margin-top: 0.7em; font-size: 0.9em; color: #475569;">a:</p>
                                <p class="font-serif font-bold" style="font-size: 2.2em; line-height: 1.2;">{{ $sampleSpeaker->name }}</p>

                                <p data-preview="body" style="margin: 0.8em auto 0; width: 82%; font-size: 0.95em; line-height: 1.5; color: #334155;"></p>

                                <div data-preview="signers" class="flex justify-center gap-[10%]" style="margin-top: 1.8em;"></div>

                                <p data-preview="footer" style="margin-top: 1.4em; font-size: 0.62em; color: #94a3b8;"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="text-xs text-gray-500 mt-3">
                    La vista previa es aproximada. Para ver el resultado exacto, guarda y abre «Ver PDF de muestra».
                </p>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('template-form');
            const body = document.getElementById('body');
            const sample = @json($sampleValues);
            const defaults = @json($defaults);
            const known = @json(array_keys($variables));
            const issued = @json(now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY'));
            const morado = @json(config('aldea.marca.900')); // tono oscuro del color institucional

            const field = (name) => form.elements[name].value.trim();
            const escape = (text) => text.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            const preview = (name) => document.querySelector(`[data-preview="${name}"]`);

            // Mismo reemplazo que CertificateTemplate::renderBody(); las variables desconocidas se marcan en rojo
            const renderBody = () => escape(field('body')).replace(/\{([^}]*)\}/g, (match, key) => {
                if (!known.includes(key)) {
                    return `<span style="background:#fee2e2;color:#b91c1c;">${match}</span>`;
                }
                const value = key === 'evento' ? field('event_name') : sample[key];
                return `<strong style="color:${morado};">${escape(String(value ?? ''))}</strong>`;
            });

            const update = () => {
                ['institution', 'event_name', 'heading', 'title'].forEach((name) => {
                    preview(name).textContent = field(name);
                });
                preview('body').innerHTML = renderBody();

                const signers = [[field('signer1_name'), field('signer1_title')], [field('signer2_name'), field('signer2_title')]]
                    .filter(([name]) => name !== '');
                preview('signers').innerHTML = signers.map(([name, title]) => `
                    <div style="width: 32%; border-top: 0.1em solid ${morado}; padding-top: 0.3em;">
                        <p style="font-size: 0.75em; font-weight: bold; color: ${morado};">${escape(name)}</p>
                        <p style="font-size: 0.62em; color: #64748b;">${escape(title)}</p>
                    </div>`).join('');

                preview('footer').textContent = [field('footer'), `Emitido el ${issued}`, 'Folio AT26-P-00000-000-XXXXXX']
                    .filter(Boolean).join(' · ');
            };

            // Insertar una variable donde está el cursor
            document.querySelectorAll('[data-variable]').forEach((button) => {
                button.addEventListener('click', () => {
                    const text = `{${button.dataset.variable}}`;
                    const start = body.selectionStart ?? body.value.length;
                    const end = body.selectionEnd ?? body.value.length;
                    body.setRangeText(text, start, end, 'end');
                    body.focus();
                    update();
                });
            });

            // Volver a los textos originales (se aplican al guardar)
            document.getElementById('reset-defaults').addEventListener('click', () => {
                Object.entries(defaults).forEach(([name, value]) => {
                    if (form.elements[name]) form.elements[name].value = value ?? '';
                });
                update();
            });

            form.querySelectorAll('[data-preview-input]').forEach((input) => input.addEventListener('input', update));
            update();
        })();
    </script>
@endpush
