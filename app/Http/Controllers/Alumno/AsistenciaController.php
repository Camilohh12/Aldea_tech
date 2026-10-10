<?php

namespace App\Http\Controllers\Alumno;

use App\Exceptions\EnrollmentException;
use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AsistenciaController extends Controller
{
    public function __construct(private EnrollmentService $service)
    {
    }

    /** Pantalla con la cámara para escanear el QR del aula. */
    public function escaner(Request $request): View
    {
        // Para mostrar el nombre de la actividad aunque no haya Internet, mandamos
        // un mapa hash(token) => datos de las actividades del alumno. Con el hash
        // no se puede reconstruir el código del QR, así que no se filtra.
        $catalogo = Enrollment::registered()
            ->where('user_id', $request->user()->id)
            ->with(['activity.type', 'activity.room'])
            ->get()
            ->filter(fn (Enrollment $e) => $e->activity->checkin_token)
            ->mapWithKeys(fn (Enrollment $e) => [
                hash('sha256', $e->activity->checkin_token) => $e->activity->summary(),
            ]);

        return view('alumno.escaner', ['catalogoOffline' => $catalogo]);
    }

    /** Registro en línea (escaneo con Internet). */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:64'],
        ]);

        try {
            $enrollment = $this->service->checkIn($request->user(), $data['token']);
        } catch (EnrollmentException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json($this->exito($enrollment));
    }

    /**
     * Sincroniza los escaneos que se guardaron en LocalStorage sin conexión.
     * Devuelve un resultado por cada registro para que el navegador sepa
     * cuáles borrar de la cola.
     */
    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'max:50'],
            'items.*.id' => ['required', 'string', 'max:64'],
            'items.*.token' => ['required', 'string', 'max:64'],
            'items.*.scanned_at' => ['required', 'date'],
        ]);

        $results = collect($data['items'])->map(function (array $item) use ($request) {
            try {
                $enrollment = $this->service->checkIn(
                    $request->user(),
                    $item['token'],
                    Carbon::parse($item['scanned_at'])->setTimezone(config('app.timezone')),
                );

                return ['id' => $item['id']] + $this->exito($enrollment);
            } catch (EnrollmentException $e) {
                return ['id' => $item['id'], 'ok' => false, 'message' => $e->getMessage()];
            }
        });

        return response()->json(['results' => $results->values()]);
    }

    private function exito(Enrollment $enrollment): array
    {
        return [
            'ok' => true,
            'message' => "Asistencia registrada en «{$enrollment->activity->title}».",
            'activity' => $enrollment->activity->summary(),
            'checkin_time' => $enrollment->checkin_time?->format('H:i'),
        ];
    }
}
