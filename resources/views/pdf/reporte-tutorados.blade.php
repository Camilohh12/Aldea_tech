<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de asistencia de tutorados</title>
    @php
        $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 1), '0'), '.');
        $fecha = $generado->locale('es')->isoFormat('D [de] MMMM [de] YYYY, HH:mm');
    @endphp
    <style>
        @page { margin: 34px 38px 52px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5px; color: #1b1530; }

        .pie { position: fixed; bottom: -34px; left: 0; right: 0; font-size: 7px; color: #6b6b80; }

        .encabezado { width: 100%; border-bottom: 3px solid #340158; padding-bottom: 8px; margin-bottom: 12px; }
        .encabezado td { vertical-align: middle; }
        .escudo { width: 50px; }
        /* El escudo es claro: va sobre fondo morado, igual que en los certificados */
        .escudo img { width: 36px; background: #26093f; padding: 3px; border-radius: 4px; }
        .institucion { font-size: 8px; color: #6f528a; text-transform: uppercase; letter-spacing: 1px; }
        h1 { font-size: 15px; color: #340158; margin: 2px 0 0; }

        .datos { width: 100%; margin-bottom: 12px; }
        .datos td { padding: 1.5px 0; }
        .etiqueta { color: #6f528a; width: 92px; }

        .resumen { width: 100%; border-collapse: separate; border-spacing: 5px 0; margin: 0 -5px 14px; }
        .resumen td { background: #f5eef8; border-left: 3px solid #4b1f6f; padding: 6px 8px; width: 16.6%; }
        .resumen .numero { font-size: 15px; font-weight: bold; color: #340158; }
        .resumen .texto { font-size: 7px; color: #6f528a; text-transform: uppercase; }

        h2 { font-size: 10.5px; color: #340158; margin: 14px 0 6px; }

        table.lista { width: 100%; border-collapse: collapse; }
        table.lista th { background: #340158; color: #fff; text-align: left; padding: 4px 5px; font-size: 7px; text-transform: uppercase; }
        table.lista td { padding: 4px 5px; border-bottom: 1px solid #e8dff0; vertical-align: top; }
        .centro { text-align: center; }
        .gris { color: #6b6b80; }

        .estado { padding: 1px 4px; font-size: 7px; font-weight: bold; }
        .acreditado { background: #d1fae5; color: #065f46; }
        .en_progreso { background: #ede3f5; color: #4b1f6f; }
        .pendiente { background: #fee2e2; color: #991b1b; }

        .alumno { page-break-inside: avoid; margin-bottom: 10px; }
        .alumno-titulo { background: #f5eef8; padding: 4px 6px; color: #340158; font-weight: bold; }
        .alumno-titulo span { font-weight: normal; color: #6f528a; }
        .vacio { color: #6b6b80; font-style: italic; padding: 4px 6px; }
        .asistio { color: #065f46; font-weight: bold; }
        .falto { color: #991b1b; }
    </style>
</head>
<body>

    <div class="pie">Aldea Tecnológica UPP 2026 · Reporte de asistencia de tutorados · Generado el {{ $fecha }}</div>

    <!-- Encabezado -->
    <table class="encabezado">
        <tr>
            @if ($escudo)
                <td class="escudo"><img src="{{ $escudo }}" alt="Escudo UPP"></td>
            @endif
            <td>
                <div class="institucion">Universidad Politécnica de Pachuca · Aldea Tecnológica 2026</div>
                <h1>Reporte de asistencia de tutorados</h1>
            </td>
        </tr>
    </table>

    <!-- Datos del reporte -->
    <table class="datos">
        <tr><td class="etiqueta">Tutor</td><td><strong>{{ $tutor->name }}</strong> · {{ $tutor->email }}</td></tr>
        <tr><td class="etiqueta">Generado</td><td>{{ $fecha }}</td></tr>
        <tr><td class="etiqueta">Meta de liberación</td><td>{{ $fmt($meta) }} horas con asistencia validada</td></tr>
        @if ($filtro)
            <tr><td class="etiqueta">Filtro aplicado</td><td>{{ $filtro }}</td></tr>
        @endif
    </table>

    <!-- Resumen -->
    <table class="resumen">
        <tr>
            <td><div class="numero">{{ $tutorados->count() }}</div><div class="texto">Tutorados</div></td>
            <td><div class="numero">{{ $resumen['acreditado'] }}</div><div class="texto">Acreditados</div></td>
            <td><div class="numero">{{ $resumen['en_progreso'] }}</div><div class="texto">En progreso</div></td>
            <td><div class="numero">{{ $resumen['pendiente'] }}</div><div class="texto">Pendientes</div></td>
            <td><div class="numero">{{ $tutorados->sum('asistencias') }}</div><div class="texto">Asistencias</div></td>
            <td><div class="numero">{{ $fmt($tutorados->sum('horas')) }}</div><div class="texto">Horas acumuladas</div></td>
        </tr>
    </table>

    <!-- Tabla general -->
    <h2>Avance por alumno</h2>
    <table class="lista">
        <thead>
            <tr>
                <th style="width: 14px;">#</th>
                <th>Alumno</th>
                <th>Carrera</th>
                <th class="centro">Cuatri.</th>
                <th class="centro">Inscr.</th>
                <th class="centro">Asist.</th>
                <th class="centro">Horas</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tutorados as $i => $p)
                <tr>
                    <td class="gris">{{ $i + 1 }}</td>
                    <td><strong>{{ $p->alumno->name }}</strong><br><span class="gris">{{ $p->alumno->matricula ?? 'Sin matrícula' }}</span></td>
                    <td>{{ $p->alumno->carrera ?? '—' }}</td>
                    <td class="centro">{{ is_numeric($p->alumno->cuatrimestre) ? $p->alumno->cuatrimestre.'°' : ($p->alumno->cuatrimestre ?? '—') }}</td>
                    <td class="centro">{{ $p->inscripcionesActivas }}</td>
                    <td class="centro">{{ $p->asistencias }}</td>
                    <td class="centro">{{ $fmt($p->horas) }} / {{ $fmt($p->meta) }}</td>
                    <td><span class="estado {{ $p->estado }}">{{ $p->etiqueta() }}</span></td>
                </tr>
            @empty
                <tr><td colspan="8" class="vacio">No hay tutorados {{ $filtro ? 'con el filtro aplicado' : 'asignados' }}.</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- Detalle por alumno -->
    @if ($tutorados->isNotEmpty())
        <h2>Detalle de asistencias</h2>

        @foreach ($tutorados as $p)
            @php $inscripciones = $p->inscripciones->filter(fn ($e) => $e->isRegistered() && $e->activity); @endphp
            <div class="alumno">
                <div class="alumno-titulo">
                    {{ $p->alumno->name }}
                    <span>· {{ $p->alumno->matricula ?? 'Sin matrícula' }} · {{ $fmt($p->horas) }} de {{ $fmt($p->meta) }} horas · {{ $p->etiqueta() }}</span>
                </div>

                @if ($inscripciones->isEmpty())
                    <div class="vacio">Sin inscripciones activas.</div>
                @else
                    <table class="lista">
                        <thead>
                            <tr>
                                <th>Actividad</th>
                                <th style="width: 92px;">Fecha y horario</th>
                                <th class="centro" style="width: 34px;">Horas</th>
                                <th style="width: 78px;">Asistencia</th>
                                <th style="width: 110px;">Registró</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($inscripciones as $e)
                                @php $a = $e->activity; @endphp
                                <tr>
                                    <td>{{ $a->title }}<br><span class="gris">{{ $a->activityType?->name ?? 'Actividad' }} · {{ $a->room?->name ?? 'Sede por confirmar' }}</span></td>
                                    <td>{{ $a->date ? \Illuminate\Support\Str::ucfirst($a->date->locale('es')->isoFormat('ddd D MMM')) : 'Por definir' }}<br><span class="gris">{{ $a->timeRange() }} h</span></td>
                                    <td class="centro">{{ $fmt($a->creditedHours()) }}</td>
                                    <td>
                                        @if ($e->attended)
                                            <span class="asistio">Asistió</span>{{ $e->checkin_time ? ' · '.$e->checkin_time->format('H:i') : '' }}
                                        @elseif ($a->hasEnded())
                                            <span class="falto">No asistió</span>
                                        @else
                                            <span class="gris">Pendiente</span>
                                        @endif
                                    </td>
                                    <td class="gris">{{ $e->attended ? ($e->checkedInBy?->name ?? '—') : '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @endforeach
    @endif

</body>
</html>
