<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Room;
use App\Models\Speaker;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

// Certificados de ponentes: lista, plantilla editable y PDF
class CertificateController extends Controller
{
    // Lista de certificados: una fila por cada ponente de cada actividad
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $estado = $request->query('estado', 'todos');

        $activities = Activity::with(['activityType', 'room', 'speakers'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $issued = Certificate::all()->keyBy(fn (Certificate $c) => $c->activity_id . '-' . $c->speaker_id);

        $rows = $activities->flatMap(fn (Activity $activity) => $activity->speakers->map(
            function (Speaker $speaker) use ($activity, $issued) {
                $certificate = $issued->get($activity->id . '-' . $speaker->id);

                return (object) [
                    'activity' => $activity,
                    'speaker' => $speaker,
                    'certificate' => $certificate,
                    'estado' => $certificate ? 'emitido' : ($activity->hasEnded() ? 'listo' : 'pendiente'),
                ];
            }
        ));

        $counts = [
            'todos' => $rows->count(),
            'listo' => $rows->where('estado', 'listo')->count(),
            'emitido' => $rows->where('estado', 'emitido')->count(),
            'pendiente' => $rows->where('estado', 'pendiente')->count(),
        ];

        // Primero los que están listos para generar
        $order = ['listo' => 0, 'emitido' => 1, 'pendiente' => 2];

        $filtered = $rows
            ->when(isset($order[$estado]), fn ($c) => $c->where('estado', $estado))
            ->when($search !== '', fn ($c) => $c->filter(
                fn ($row) => Str::contains($row->speaker->name . ' ' . $row->activity->title, $search, ignoreCase: true)
            ))
            ->sortBy(fn ($row) => [
                $order[$row->estado],
                $row->activity->date?->timestamp ?? PHP_INT_MAX,
                $row->speaker->name,
            ])
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginated = new LengthAwarePaginator(
            $filtered->forPage($page, 15)->values(),
            $filtered->count(),
            15,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.certificates.index', [
            'rows' => $paginated,
            'counts' => $counts,
            'search' => $search,
            'estado' => $estado,
            'withoutSpeakers' => $activities->filter(fn (Activity $a) => $a->speakers->isEmpty())->count(),
        ]);
    }

    // Editor de la plantilla general con vista previa
    public function editTemplate()
    {
        $template = CertificateTemplate::current();
        [$activity, $speaker] = $this->sampleData();

        return view('admin.certificates.template', [
            'template' => $template,
            'sampleValues' => $template->valuesFor($activity, $speaker),
            'sampleSpeaker' => $speaker,
            'variables' => CertificateTemplate::VARIABLES,
            'defaults' => CertificateTemplate::DEFAULTS,
        ]);
    }

    // Guardar la plantilla
    public function updateTemplate(Request $request)
    {
        $validated = $request->validate([
            'institution' => ['required', 'string', 'max:120'],
            'event_name' => ['required', 'string', 'max:120'],
            'heading' => ['required', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:40'],
            'body' => ['required', 'string', 'max:1000'],
            'signer1_name' => ['required', 'string', 'max:120'],
            'signer1_title' => ['required', 'string', 'max:120'],
            'signer2_name' => ['nullable', 'string', 'max:120', 'required_with:signer2_title'],
            'signer2_title' => ['nullable', 'string', 'max:120', 'required_with:signer2_name'],
            'footer' => ['nullable', 'string', 'max:160'],
        ], $this->messages());

        // Solo se permiten las variables de la lista
        preg_match_all('/\{([^}]*)\}/', $validated['body'], $found);
        $unknown = array_diff($found[1], array_keys(CertificateTemplate::VARIABLES));

        if ($unknown) {
            return back()
                ->withInput()
                ->withErrors(['body' => 'Estas variables no existen: {' . implode('}, {', $unknown) . '}. Usa solo las de la lista.']);
        }

        CertificateTemplate::current()->update($validated);

        return redirect()
            ->route('admin.certificates.template')
            ->with('success', 'Plantilla del certificado guardada.');
    }

    // PDF de muestra con la plantilla guardada (se abre en el navegador)
    public function sample()
    {
        [$activity, $speaker] = $this->sampleData();

        return $this->pdf($activity, $speaker, 'AT26-P-MUESTRA', now())
            ->stream('certificado-muestra.pdf');
    }

    // Generar (la primera vez) o volver a descargar el certificado de un ponente
    public function download(Activity $activity, Speaker $speaker)
    {
        $activity->load(['activityType', 'room', 'speakers']);

        abort_unless($activity->speakers->contains($speaker), 404);

        if (! $activity->hasEnded()) {
            return redirect()
                ->route('admin.certificates.index')
                ->with('error', "El certificado de «{$speaker->name}» se genera cuando termine la actividad.");
        }

        // La primera descarga emite el certificado; las siguientes reimprimen el mismo folio
        $certificate = Certificate::where('activity_id', $activity->id)
            ->where('speaker_id', $speaker->id)
            ->first();

        $folio = $certificate?->folio ?? Certificate::folioFor($activity, $speaker);
        $issuedAt = $certificate?->issued_at ?? now();

        // Primero se arma el PDF; solo si sale bien se registra como emitido
        $content = $this->pdf($activity, $speaker, $folio, $issuedAt)->output();

        if (! $certificate) {
            Certificate::create([
                'activity_id' => $activity->id,
                'speaker_id' => $speaker->id,
                'folio' => $folio,
                'issued_by' => auth()->id(),
                'issued_at' => $issuedAt,
            ]);
        }

        $filename = 'Certificado-' . Str::slug($speaker->name) . '-' . Str::slug(Str::limit($activity->title, 40, '')) . '.pdf';

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // Arma el PDF con la plantilla vigente
    private function pdf(Activity $activity, Speaker $speaker, string $folio, $issuedAt)
    {
        $template = CertificateTemplate::current();
        $escudo = public_path('img/escudo-upp.png');

        // DomPDF necesita la extensión GD de PHP para las imágenes;
        // sin ella, el certificado se genera sin el escudo en lugar de fallar
        $withShield = extension_loaded('gd') && is_file($escudo);

        return Pdf::loadView('pdf.certificate', [
            'template' => $template,
            'speaker' => $speaker,
            'bodyHtml' => $template->renderBody($template->valuesFor($activity, $speaker)),
            'folio' => $folio,
            'issuedAt' => $issuedAt,
            'escudo' => $withShield ? 'data:image/png;base64,' . base64_encode(file_get_contents($escudo)) : null,
        ])
            ->setPaper('letter', 'landscape')
            ->setOption('isFontSubsettingEnabled', true); // PDF más ligero
    }

    // Datos para la vista previa: un ponente real o, si no hay, un ejemplo
    private function sampleData(): array
    {
        $activity = Activity::with(['activityType', 'room', 'speakers'])
            ->has('speakers')
            ->orderByDesc('date')
            ->first();

        if ($activity) {
            return [$activity, $activity->speakers->first()];
        }

        $activity = (new Activity)->forceFill([
            'title' => 'Taller de Ciberseguridad Defensiva',
            'date' => config('aldea.event.start_date'),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'duration_hours' => 2,
        ]);
        $activity->setRelation('activityType', (new ActivityType)->forceFill(['name' => 'Taller']));
        $activity->setRelation('room', (new Room)->forceFill(['name' => 'Laboratorio de Redes']));

        $speaker = (new Speaker)->forceFill([
            'name' => 'Dra. Mónica P. Soto',
            'bio' => 'Investigadora en ciberseguridad',
        ]);

        return [$activity, $speaker];
    }

    // Mensajes de validación en español
    private function messages(): array
    {
        return [
            'institution.required' => 'Escribe el nombre de la institución.',
            'event_name.required' => 'Escribe el nombre del evento.',
            'heading.required' => 'Escribe el encabezado (ej. «OTORGA LA PRESENTE»).',
            'title.required' => 'Escribe el título (ej. «CONSTANCIA» o «RECONOCIMIENTO»).',
            'title.max' => 'El título no puede tener más de 40 caracteres.',
            'body.required' => 'Escribe el texto del certificado.',
            'body.max' => 'El texto no puede tener más de 1,000 caracteres.',
            'signer1_name.required' => 'Escribe el nombre de quien firma.',
            'signer1_title.required' => 'Escribe el cargo de quien firma.',
            'signer2_name.required_with' => 'Si agregas un segundo cargo, escribe también el nombre.',
            'signer2_title.required_with' => 'Si agregas una segunda firma, escribe también el cargo.',
            '*.max' => 'Este campo es demasiado largo.',
        ];
    }
}
