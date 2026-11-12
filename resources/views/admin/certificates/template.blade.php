@extends('layouts.admin')

@section('title', 'Plantilla del certificado')

@section('content')
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
                    <label for="institution" class="block text-sm font-semibold text-upp-primary mb-1.5">
                        Institución <span class="font-normal text-gray-500">(no se imprime: el escudo ya la muestra)</span>
                    </label>
                    <input type="text" id="institution" name="institution" maxlength="120" required data-preview-input
                           value="{{ $value('institution') }}" class="{{ $field }} {{ $border('institution') }}">
                    @error('institution') <p class="text-red-600 text-sm mt-1.5">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="event_name" class="block text-sm font-semibold text-upp-primary mb-1.5">
                        Nombre del evento <span class="font-normal text-gray-500">(se usa en el texto con {evento})</span>
                    </label>
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
                               value="{{ $value('title') }}" placeholder="RECONOCIMIENTO"
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
                           value="{{ $value('footer') }}" placeholder="Normalmente se deja vacío"
                           class="{{ $field }} {{ $border('footer') }}">
                    <p class="text-xs text-gray-500 mt-1.5">Va en letra chica bajo el marco, junto con el folio y la fecha de emisión.</p>
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

                <!-- Hoja carta horizontal a escala (11 × 8.5) con el fondo del formato oficial.
                     Medidas iguales a pdf/certificate.blade.php: 1cqw = 2.794 mm y 1 pt = 0.1263cqw -->
                <div class="bg-white rounded-xl shadow-sm p-3">
                    <div class="relative w-full" style="padding-bottom: 77.27%; container-type: inline-size;">
                        <div class="absolute inset-0 bg-white overflow-hidden text-black" style="font-family: Inter, sans-serif;">
                            <img src="{{ asset('img/certificado/fondo-reconocimiento.jpg') }}" alt="" class="absolute inset-0 w-full h-full">

                            <p data-preview="heading" class="absolute text-center font-light" style="top: 26.77%; left: 7.16%; right: 7.16%; font-size: 1.77cqw; line-height: 1.2;"></p>

                            <p class="absolute text-center font-bold italic" style="top: 31.26%; left: 7.16%; right: 7.16%; font-size: 4.92cqw; line-height: 1.1;">
                                <span data-preview="title"></span> <span style="font-size: 4.42cqw;">a:</span>
                            </p>

                            <div class="absolute text-center" style="top: 41.87%; left: 6.44%; right: 6.44%;">
                                <p class="font-semibold" style="font-size: {{ mb_strlen($sampleSpeaker->name) <= 36 ? '4.04' : (mb_strlen($sampleSpeaker->name) <= 46 ? '3.28' : '2.78') }}cqw; line-height: 1.12;">{{ $sampleSpeaker->name }}</p>
                                <p data-preview="body" class="font-light" style="margin: 1.65cqw 2.86cqw 0; font-size: 1.77cqw; line-height: 1.3;"></p>
                            </div>

                            <div data-preview="signers" class="absolute flex justify-around text-center" style="top: 71.65%; left: 10.74%; right: 10.74%;"></div>

                            <p data-preview="footer" class="absolute text-right" style="bottom: 0.4%; right: 7.9%; font-size: 0.82cqw; color: #8a8496;"></p>
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
            const negritas = @json(\App\Models\CertificateTemplate::EN_NEGRITAS);

            const field = (name) => form.elements[name].value.trim();
            const escape = (text) => text.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            const preview = (name) => document.querySelector(`[data-preview="${name}"]`);

            // Mismo reemplazo que CertificateTemplate::renderBody(); las variables desconocidas se marcan en rojo
            const renderBody = () => escape(field('body')).replace(/\{([^}]*)\}/g, (match, key) => {
                if (!known.includes(key)) {
                    return `<span style="background:#fee2e2;color:#b91c1c;">${match}</span>`;
                }
                const value = key === 'evento' ? field('event_name') : sample[key];
                const text = escape(String(value ?? ''));
                return negritas.includes(key) ? `<strong class="font-semibold">${text}</strong>` : text;
            });

            const update = () => {
                ['heading', 'title'].forEach((name) => {
                    preview(name).textContent = field(name);
                });
                preview('body').innerHTML = renderBody();

                const signers = [[field('signer1_name'), field('signer1_title')], [field('signer2_name'), field('signer2_title')]]
                    .filter(([name]) => name !== '');
                preview('signers').innerHTML = signers.map(([name, title]) => `
                    <div style="width: ${signers.length === 1 ? 100 : 50}%;">
                        <p style="font-size: 1.58cqw; line-height: 1.2; font-weight: bold;">${escape(name)}</p>
                        <p style="font-size: 1.52cqw; line-height: 1.25; color: #222;">${escape(title)}</p>
                    </div>`).join('');

                preview('footer').textContent = [field('footer'), 'Folio AT26-P-00000-00-XXXXXX', `Emitido el ${issued}`]
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
