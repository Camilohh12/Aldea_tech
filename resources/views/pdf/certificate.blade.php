{{-- Reconocimiento de ponente (DomPDF) con el formato oficial de la UPP
     (Plantilla de Reconocimientos 2026). El marco, el escudo y la franja de
     Hidalgo vienen de la imagen de fondo; los textos, de la plantilla editable. --}}
@php
    $signers = collect([
        [$template->signer1_name, $template->signer1_title],
        [$template->signer2_name, $template->signer2_title],
    ])->filter(fn ($signer) => filled($signer[0]))->values();
    $font = fn (string $file) => $fonts . '/' . $file;

    // Nombres y textos largos se reducen para que todo quepa antes de la firma
    $largoNombre = mb_strlen($speaker->name);
    $tamNombre = $largoNombre <= 36 ? 32 : ($largoNombre <= 46 ? 26 : 22);
    $tamTexto = mb_strlen(strip_tags($bodyHtml)) <= 260 ? 14 : 12;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $template->title }} · {{ $speaker->name }}</title>
    <style>
        /* Carta horizontal: 279.4 x 215.9 mm. DomPDF solo entiende CSS sencillo (sin flex/grid).
           Graphik (la letra del formato) no es libre; Inter es la más parecida. */
        @font-face { font-family: 'InterLight'; font-weight: normal; font-style: normal; src: url('{{ $font('Inter-Light.ttf') }}') format('truetype'); }
        @font-face { font-family: 'InterLight'; font-weight: bold; font-style: normal; src: url('{{ $font('Inter-SemiBold.ttf') }}') format('truetype'); }
        @font-face { font-family: 'InterSemi'; font-weight: normal; font-style: normal; src: url('{{ $font('Inter-SemiBold.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Inter'; font-weight: normal; font-style: normal; src: url('{{ $font('Inter-Regular.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Inter'; font-weight: bold; font-style: normal; src: url('{{ $font('Inter-Bold.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Inter'; font-weight: bold; font-style: italic; src: url('{{ $font('Inter-BoldItalic.ttf') }}') format('truetype'); }

        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Inter', 'DejaVu Sans', sans-serif; color: #000; }
        .fondo { position: absolute; top: 0; left: 0; width: 279.4mm; height: 215.9mm; }
        .linea { position: absolute; left: 20mm; right: 20mm; text-align: center; }
        .leyenda { top: 57.8mm; font-family: 'InterLight', 'DejaVu Sans', sans-serif; font-size: 14pt; line-height: 1.2; }
        .titulo { top: 67.5mm; font-size: 39pt; line-height: 1.1; font-weight: bold; font-style: italic; }
        .titulo span { font-size: 35pt; }
        /* Nombre y texto van juntos: si el nombre ocupa dos renglones, el texto baja */
        .centro { top: 90.4mm; left: 18mm; right: 18mm; }
        .nombre { font-family: 'InterSemi', 'DejaVu Sans', sans-serif; font-size: {{ $tamNombre }}pt; line-height: 1.12; }
        .texto { margin: 4.6mm 8mm 0; font-family: 'InterLight', 'DejaVu Sans', sans-serif; font-size: {{ $tamTexto }}pt; line-height: 1.3; }
        .texto strong { font-weight: bold; }
        .firmas { position: absolute; top: 154.7mm; left: 30mm; right: 30mm; border-collapse: collapse; }
        .firmas td { text-align: center; vertical-align: top; padding: 0; }
        .firma-nombre { font-size: 12.5pt; line-height: 1.2; font-weight: bold; }
        .firma-cargo { font-size: 12pt; line-height: 1.25; color: #222; }
        .folio { position: absolute; bottom: 1.4mm; right: 22mm; font-size: 6.5pt; color: #8a8496; letter-spacing: 0.2pt; }
    </style>
</head>
<body>
    @if ($fondo)
        <img class="fondo" src="{{ $fondo }}" alt="">
    @endif

    <div class="linea leyenda">{{ $template->heading }}</div>
    <div class="linea titulo">{{ $template->title }} <span>a:</span></div>
    <div class="linea centro">
        <div class="nombre">{{ $speaker->name }}</div>
        {{-- El texto ya viene escapado por CertificateTemplate::renderBody() --}}
        <div class="texto">{!! $bodyHtml !!}</div>
    </div>

    <table class="firmas" style="width: 219.4mm;">
        <tr>
            @foreach ($signers as [$name, $title])
                <td style="width: {{ $signers->count() === 1 ? '100' : '50' }}%;">
                    <div class="firma-nombre">{{ $name }}</div>
                    <div class="firma-cargo">{{ $title }}</div>
                </td>
            @endforeach
        </tr>
    </table>

    <div class="folio">
        @if ($template->footer)
            {{ $template->footer }} ·
        @endif
        Folio {{ $folio }} · Emitido el {{ $issuedAt->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}
    </div>
</body>
</html>
