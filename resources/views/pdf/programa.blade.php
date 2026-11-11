<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Programa · Aldea Tecnológica UPP 2026</title>
    @php
        // Colores de resources/css/marca.json (misma escala "aldea" de las pantallas)
        $m = $marca;
        $periodo = $inicio->isSameMonth($fin)
            ? $inicio->isoFormat('D').' al '.$fin->isoFormat('D [de] MMMM [de] YYYY')
            : $inicio->isoFormat('D [de] MMMM').' al '.$fin->isoFormat('D [de] MMMM [de] YYYY');
    @endphp
    <style>
        @page { margin: 34px 40px 56px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #1b1530; }

        .pie { position: fixed; bottom: -36px; left: 0; right: 0; font-size: 7.5px; color: #6b6b80;
               border-top: 1px solid {{ $m[200] }}; padding-top: 5px; }

        /* Encabezado con la identidad del evento */
        .encabezado { width: 100%; border-collapse: collapse; }
        .encabezado td { vertical-align: middle; }
        .escudo { width: 58px; }
        .escudo img { width: 50px; }
        .institucion { font-size: 7.5px; color: {{ $m[500] }}; text-transform: uppercase; letter-spacing: 1.2px; font-weight: bold; }
        h1 { font-size: 19px; color: {{ $m[800] }}; margin: 2px 0 1px; }
        .subtitulo { font-size: 10px; color: #3f3a52; }
        .carreras { text-align: right; white-space: nowrap; }
        .carreras img { height: 46px; margin-left: 6px; }
        .banda { height: 4px; background: {{ $m[800] }}; margin: 10px 0 12px; }

        .resumen { font-size: 8px; color: #55506a; margin-bottom: 12px; }
        .resumen strong { color: {{ $m[800] }}; }

        /* Un bloque por día; en el programa completo cada día empieza en hoja nueva */
        .dia { page-break-before: always; }
        .dia.primero { page-break-before: auto; }
        .dia-titulo { width: 100%; border-collapse: collapse; background: {{ $m[800] }}; color: #fff; }
        .dia-titulo td { padding: 7px 10px; }
        .dia-nombre { font-size: 12.5px; font-weight: bold; }
        .dia-cuenta { text-align: right; font-size: 8px; color: {{ $m[100] }}; }

        table.programa { width: 100%; border-collapse: collapse; }
        table.programa th { background: {{ $m[100] }}; color: {{ $m[800] }}; text-align: left; padding: 5px 8px;
                            font-size: 7px; text-transform: uppercase; letter-spacing: .6px; border-bottom: 1.5px solid {{ $m[300] }}; }
        table.programa td { padding: 7px 8px; border-bottom: 1px solid {{ $m[100] }}; vertical-align: top; }
        table.programa tr { page-break-inside: avoid; }
        table.programa tr.par td { background: {{ $m[50] }}; }

        .hora { width: 66px; font-weight: bold; color: {{ $m[800] }}; font-size: 9.5px; white-space: nowrap; }
        .titulo { font-weight: bold; font-size: 9.5px; color: #1b1530; }
        .tipo { display: inline-block; margin-top: 3px; padding: 1px 5px; font-size: 6.5px; font-weight: bold;
                text-transform: uppercase; letter-spacing: .5px; color: {{ $m[700] }}; background: {{ $m[100] }}; }
        .aula { width: 92px; }
        .personas { width: 170px; }
        .gris { color: #6b6b80; }
        .etiqueta { font-size: 7px; color: #6b6b80; text-transform: uppercase; letter-spacing: .4px; }
        .vacio { padding: 14px 8px; color: #6b6b80; font-style: italic; }
    </style>
</head>
<body>

    <div class="pie">
        Aldea Tecnológica UPP 2026 · Programa sujeto a cambios · Generado el {{ $generado->locale('es')->isoFormat('D [de] MMMM [de] YYYY, HH:mm') }}
    </div>

    <!-- Encabezado: escudo de la UPP, nombre del evento y logos de las carreras -->
    <table class="encabezado">
        <tr>
            @if ($escudo)
                <td class="escudo"><img src="{{ $escudo }}" alt="Escudo de la UPP"></td>
            @endif
            <td>
                <div class="institucion">Universidad Politécnica de Pachuca</div>
                <h1>Aldea Tecnológica 2026</h1>
                <div class="subtitulo">
                    @if ($unDia)
                        Programa del {{ $unDia->isoFormat('dddd D [de] MMMM [de] YYYY') }}
                    @else
                        Programa de actividades · {{ $periodo }}
                    @endif
                </div>
            </td>
            @if ($logosCarreras)
                <td class="carreras">
                    @foreach ($logosCarreras as $carrera => $logo)
                        <img src="{{ $logo }}" alt="{{ $carrera }}">
                    @endforeach
                </td>
            @endif
        </tr>
    </table>
    <div class="banda"></div>

    <div class="resumen">
        <strong>{{ $total }}</strong> {{ $total === 1 ? 'actividad programada' : 'actividades programadas' }}
        @unless ($unDia)
            en {{ $dias->count() }} {{ $dias->count() === 1 ? 'día' : 'días' }}
        @endunless
        · Horario del centro de México
    </div>

    @foreach ($dias as $bloque)
        <div class="dia {{ $loop->first ? 'primero' : '' }}">
            <table class="dia-titulo">
                <tr>
                    <td class="dia-nombre">{{ $bloque->titulo }}</td>
                    <td class="dia-cuenta">
                        {{ $bloque->actividades->count() }} {{ $bloque->actividades->count() === 1 ? 'actividad' : 'actividades' }}
                    </td>
                </tr>
            </table>

            @if ($bloque->actividades->isEmpty())
                <div class="vacio">Sin actividades programadas para este día.</div>
            @else
                <table class="programa">
                    <thead>
                        <tr>
                            <th>Horario</th>
                            <th>Actividad</th>
                            <th>Aula</th>
                            <th>Ponente / responsable</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bloque->actividades as $actividad)
                            <tr class="{{ $loop->even ? 'par' : '' }}">
                                <td class="hora">{{ $actividad->timeRange() }}</td>
                                <td>
                                    <div class="titulo">{{ $actividad->title }}</div>
                                    @if ($actividad->activityType)
                                        <span class="tipo">{{ $actividad->activityType->name }}</span>
                                    @endif
                                </td>
                                <td class="aula">{{ $actividad->room?->name ?? 'Por confirmar' }}</td>
                                <td class="personas">
                                    @if ($actividad->speakers->isNotEmpty())
                                        <div class="etiqueta">{{ $actividad->speakers->count() > 1 ? 'Ponentes' : 'Ponente' }}</div>
                                        <div>{{ $actividad->speakerNames() }}</div>
                                    @endif
                                    @if ($actividad->responsible)
                                        <div class="etiqueta" @if ($actividad->speakers->isNotEmpty()) style="margin-top: 3px;" @endif>Responsable</div>
                                        <div class="{{ $actividad->speakers->isNotEmpty() ? 'gris' : '' }}">{{ $actividad->responsible->name }}</div>
                                    @endif
                                    @if ($actividad->speakers->isEmpty() && ! $actividad->responsible)
                                        <span class="gris">Por confirmar</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach

</body>
</html>
