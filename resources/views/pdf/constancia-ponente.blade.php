@php
    $fecha = $actividad->date
        ? $actividad->date->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY')
        : null;
    $inicio = $actividad->start_time ? substr($actividad->start_time, 0, 5) : null;
    $fin = $actividad->end_time ? substr($actividad->end_time, 0, 5) : null;

    // Arma el párrafo por partes para omitir los datos que falten.
    $partes = [];
    if ($fecha) {
        $partes[] = 'realizada el '.e($fecha).($inicio && $fin ? " de {$inicio} a {$fin} h" : '')
            .($actividad->room ? ' en '.e($actividad->room->name) : '');
    }
    if ($asistentes > 0) {
        $partes[] = 'con una asistencia registrada de '.$asistentes.' '.($asistentes === 1 ? 'participante' : 'participantes');
    }
    $tipo = $actividad->type ? ' ('.e(mb_strtolower($actividad->type->name)).')' : '';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Constancia de ponente · {{ $ponente->name }}</title>
    <style>
        /* Carta horizontal: 279.4 x 215.9 mm. DomPDF solo entiende CSS sencillo (sin flex/grid). */
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'DejaVu Sans', sans-serif; color: #1b1530; }

        .marco { position: absolute; top: 9mm; left: 9mm; right: 9mm; bottom: 9mm; border: 3mm solid #4a1f80; }
        .marco-interno { position: absolute; top: 13.5mm; left: 13.5mm; right: 13.5mm; bottom: 13.5mm; border: 0.4mm solid #b49bd6; }
        .esquina { position: absolute; width: 14mm; height: 14mm; border-color: #c9a227; border-style: solid; }
        .e1 { top: 16mm; left: 16mm; border-width: 0.8mm 0 0 0.8mm; }
        .e2 { top: 16mm; right: 16mm; border-width: 0.8mm 0.8mm 0 0; }
        .e3 { bottom: 16mm; left: 16mm; border-width: 0 0 0.8mm 0.8mm; }
        .e4 { bottom: 16mm; right: 16mm; border-width: 0 0.8mm 0.8mm 0; }

        .contenido { position: absolute; top: 22mm; left: 24mm; right: 24mm; bottom: 20mm; text-align: center; }

        .encabezado { width: 100%; border-collapse: collapse; }
        .encabezado td { vertical-align: middle; }
        .escudo { height: 24mm; }
        .institucion { font-size: 9.5pt; letter-spacing: 2.5pt; font-weight: bold; color: #4a1f80; }
        .evento { font-family: 'DejaVu Serif', serif; font-size: 13pt; color: #26093f; margin-top: 1mm; }
        .folio-caja { border: 0.3mm solid #d7c8ea; padding: 2mm 3mm; font-size: 7pt; color: #5a2d93; text-align: left; }
        .folio-caja b { display: block; font-size: 8pt; white-space: nowrap; color: #26093f; letter-spacing: 0.5pt; }

        .otorga { margin-top: 9mm; font-size: 10pt; letter-spacing: 1pt; color: #475569; }
        .titulo { font-family: 'DejaVu Serif', serif; font-size: 40pt; letter-spacing: 9pt; color: #4a1f80; margin-top: 1mm; }
        .linea-oro { width: 60mm; height: 0.6mm; background: #c9a227; margin: 2mm auto 0; }
        .a { margin-top: 5mm; font-size: 10pt; color: #475569; }
        .nombre { font-family: 'DejaVu Serif', serif; font-size: 25pt; font-weight: bold; color: #1b1530; margin-top: 1.5mm; }
        .datos-alumno { font-size: 8.5pt; color: #64748b; margin-top: 1.5mm; letter-spacing: 0.4pt; }

        .texto { margin: 6mm auto 0; width: 200mm; font-size: 10.5pt; line-height: 1.55; color: #334155; }
        .texto strong { color: #26093f; }

        .firmas { width: 100%; margin-top: 12mm; border-collapse: collapse; }
        .firmas td { width: 100%; text-align: center; vertical-align: top; padding: 0 14mm; }
        .linea-firma { border-top: 0.3mm solid #26093f; margin: 0 auto; width: 70mm; padding-top: 1.5mm; }
        .firma-nombre { font-size: 9pt; font-weight: bold; color: #26093f; }
        .firma-cargo { font-size: 7.5pt; color: #64748b; margin-top: 0.5mm; }

        .pie { margin-top: 9mm; font-size: 7pt; color: #94a3b8; letter-spacing: 0.3pt; }
    </style>
</head>
<body>
    <div class="marco"></div>
    <div class="marco-interno"></div>
    <div class="esquina e1"></div><div class="esquina e2"></div><div class="esquina e3"></div><div class="esquina e4"></div>

    <div class="contenido">
        <table class="encabezado">
            <tr>
                <td style="width: 30mm; text-align: left;">
                    @if ($escudo)
                        <img class="escudo" src="{{ $escudo }}" alt="Escudo UPP" style="background:#26093f; padding:1.5mm; border-radius:2mm;">
                    @endif
                </td>
                <td style="text-align: center;">
                    <div class="institucion">UNIVERSIDAD POLITÉCNICA DE PACHUCA</div>
                    <div class="evento">Aldea Tecnológica 2026</div>
                </td>
                <td style="width: 48mm; text-align: right;">
                    <div class="folio-caja">
                        Folio
                        <b>{{ $folio }}</b>
                    </div>
                </td>
            </tr>
        </table>

        <div class="otorga">OTORGA LA PRESENTE</div>
        <div class="titulo">CONSTANCIA</div>
        <div class="linea-oro"></div>

        <div class="a">a:</div>
        <div class="nombre">{{ $ponente->name }}</div>
        @if ($ponente->bio)
            <div class="datos-alumno">{{ \Illuminate\Support\Str::limit(strtok($ponente->bio, "\n"), 90) }}</div>
        @endif

        <div class="texto">
            Por su valiosa participación como <strong>ponente</strong> en la actividad
            <strong>«{{ $actividad->title }}»</strong>{!! $tipo !!}@if ($partes), {!! implode(', ', $partes) !!}@endif,
            en el marco de Aldea Tecnológica 2026.
        </div>

        <table class="firmas">
            <tr>
                <td>
                    <div class="linea-firma">
                        <div class="firma-nombre">Coordinación de Aldea Tecnológica</div>
                        <div class="firma-cargo">Universidad Politécnica de Pachuca</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="pie">
            Folio {{ $folio }}
            · Emitida el {{ now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}
        </div>
    </div>
</body>
</html>
