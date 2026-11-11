<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Programa del evento en PDF para el administrador:
 * completo (los tres días) o de un solo día (/admin/programa/pdf/2026-11-24).
 */
class ProgramaController extends Controller
{
    public function descargar(?string $dia = null): Response
    {
        $diasDelEvento = $this->diasDelEvento();

        // Solo se aceptan los días del evento; cualquier otra fecha no existe
        abort_if($dia !== null && ! isset($diasDelEvento[$dia]), 404);

        // Solo los días del evento (el formulario de actividades ya no permite otras fechas)
        $dias = $dia ? [$dia => $diasDelEvento[$dia]] : $diasDelEvento;

        $actividades = Activity::with(['activityType', 'room', 'speakers', 'responsible'])
            // whereDate: en SQLite la fecha puede guardarse con hora ("2026-11-26 00:00:00")
            ->whereDate('date', '>=', array_key_first($dias))
            ->whereDate('date', '<=', array_key_last($dias))
            ->chronological()
            ->orderBy('end_time')
            ->orderBy('title')
            ->get();

        $pdf = Pdf::loadView('pdf.programa', [
            'dias' => $this->agruparPorDia($actividades, $dias),
            'unDia' => $dia ? $diasDelEvento[$dia] : null,
            'inicio' => reset($diasDelEvento),
            'fin' => end($diasDelEvento),
            'total' => $actividades->count(),
            'generado' => now(),
            'marca' => config('aldea.marca'),
            'escudo' => $this->imagen('img/escudo-upp-color.png'),
            'logosCarreras' => $this->logosCarreras(),
        ])
            ->setPaper('letter')
            ->setOption('isFontSubsettingEnabled', true);

        // Número de página en el pie, a la derecha (igual que el reporte de tutorados)
        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $canvas = $dompdf->getCanvas();
        $fuente = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $ancho = $dompdf->getFontMetrics()->getTextWidth('Página 00 de 00', $fuente, 6);
        $canvas->page_text($canvas->get_width() - 30 - $ancho, 768.5,'Página {PAGE_NUM} de {PAGE_COUNT}',
            $fuente, 6, [0.42, 0.42, 0.5]);

        $archivo = 'Programa-Aldea-Tecnologica-2026'
            .($dia ? '-'.Str::slug($diasDelEvento[$dia]->isoFormat('dddd D MMMM')) : '')
            .'.pdf';

        return $pdf->download($archivo);
    }

    /** ['2026-11-24' => Carbon, ...] con los días configurados en config/aldea.php */
    private function diasDelEvento(): array
    {
        $dias = [];
        foreach (CarbonPeriod::create(config('aldea.event.start_date'), config('aldea.event.end_date')) as $fecha) {
            $dias[$fecha->format('Y-m-d')] = Carbon::parse($fecha)->locale('es');
        }

        return $dias;
    }

    /** Un bloque por día, aunque ese día todavía no tenga actividades. */
    private function agruparPorDia(Collection $actividades, array $dias): Collection
    {
        $porFecha = $actividades->toBase()->groupBy(fn (Activity $a) => $a->date->format('Y-m-d'));

        return collect($dias)->map(fn (Carbon $fecha, string $clave) => (object) [
            'titulo' => Str::ucfirst($fecha->isoFormat('dddd D [de] MMMM')),
            'actividades' => $porFecha->get($clave, collect()),
        ])->values();
    }

    /**
     * Logos de las carreras en public/img/carreras, nombrados como la carrera en
     * config/aldea.php (p. ej. ingenieria-en-software.png). Si falta uno, se omite.
     */
    private function logosCarreras(): array
    {
        return collect(config('aldea.carreras'))
            ->mapWithKeys(fn (string $carrera) => [$carrera => $this->imagen('img/carreras/'.Str::slug($carrera).'.png')])
            ->filter()
            ->all();
    }

    /** Imagen como data URI. DomPDF necesita la extensión gd para los PNG; sin ella se omite. */
    private function imagen(string $ruta): ?string
    {
        $archivo = public_path($ruta);

        return extension_loaded('gd') && is_file($archivo)
            ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($archivo))
            : null;
    }
}
