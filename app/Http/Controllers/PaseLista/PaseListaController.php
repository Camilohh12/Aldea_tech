<?php

namespace App\Http\Controllers\PaseLista;

use App\Exceptions\EnrollmentException;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Enrollment;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Pase de lista: el responsable asignado a la actividad (o un administrador)
 * escanea el QR del pase de cada alumno inscrito.
 */
class PaseListaController extends Controller
{
    public function __construct(private EnrollmentService $service)
    {
    }

    /** Actividades a cargo del usuario (todas si es admin), con las de hoy primero. */
    public function index(Request $request): View
    {
        $hoy = now()->format('Y-m-d');
        $user = $request->user();

        $activities = Activity::with(['activityType', 'room'])
            ->unless($user->hasAnyRole(['admin', 'super_admin']), fn ($q) => $q->where('responsible_id', $user->id))
            ->withRegisteredCount()
            ->withCount(['enrollments as attended_count' => fn ($q) => $q
                ->where('status', Enrollment::STATUS_REGISTERED)
                ->where('attended', true)])
            ->chronological()
            ->get();

        return view('pase-lista.index', [
            'hoy' => $activities->filter(fn (Activity $a) => $a->date?->format('Y-m-d') === $hoy)->values(),
            'porDia' => $activities
                ->reject(fn (Activity $a) => $a->date?->format('Y-m-d') === $hoy)
                ->groupBy(fn (Activity $a) => $a->date?->format('Y-m-d') ?? 'sin-fecha'),
        ]);
    }

    /** Escáner + lista de alumnos inscritos en la actividad. */
    public function show(Request $request, Activity $activity): View
    {
        $this->autorizar($request, $activity);

        $activity->load(['activityType', 'room', 'speakers']);

        $inscritos = $activity->enrollments()
            ->registered()
            ->with('user')
            ->get()
            ->sortBy(fn (Enrollment $e) => mb_strtolower($e->user->name))
            ->values();

        // Lista para validar sin conexión: solo la huella del código, nunca el código.
        $roster = $inscritos->map(fn (Enrollment $e) => [
            'id' => $e->id,
            'hash' => $e->qrHash(),
            'name' => $e->user->name,
            'matricula' => $e->user->matricula,
            'attended' => $e->attended,
            'time' => $e->checkin_time?->format('H:i'),
        ]);

        return view('pase-lista.show', [
            'activity' => $activity,
            'inscritos' => $inscritos,
            'roster' => $roster,
        ]);
    }

    /** Abre o cierra el pase de lista de la actividad. */
    public function toggle(Request $request, Activity $activity): RedirectResponse
    {
        $this->autorizar($request, $activity);

        $activity->update(['checkin_enabled' => ! $activity->checkin_enabled]);

        return back()->with('success', $activity->checkin_enabled
            ? 'Pase de lista abierto. Ya puedes escanear los pases.'
            : 'Pase de lista cerrado.');
    }

    /** Escaneo en línea. */
    public function store(Request $request, Activity $activity): JsonResponse
    {
        $this->autorizar($request, $activity);

        $data = $request->validate(['token' => ['required', 'string', 'max:64']]);

        try {
            $enrollment = $this->service->checkIn($request->user(), $activity, $data['token']);
        } catch (EnrollmentException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json($this->exito($enrollment));
    }

    /** Sincroniza los escaneos que el tutor guardó sin conexión. */
    public function sync(Request $request, Activity $activity): JsonResponse
    {
        $this->autorizar($request, $activity);

        $data = $request->validate([
            'items' => ['required', 'array', 'max:200'],
            'items.*.id' => ['required', 'string', 'max:64'],
            'items.*.token' => ['required', 'string', 'max:64'],
            'items.*.scanned_at' => ['required', 'date'],
        ]);

        $results = collect($data['items'])->map(function (array $item) use ($request, $activity) {
            try {
                $enrollment = $this->service->checkIn(
                    $request->user(),
                    $activity,
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

    /** Registro manual desde la lista. */
    public function manual(Request $request, Activity $activity, int $enrollment): RedirectResponse
    {
        $this->autorizar($request, $activity);

        try {
            $registro = $this->service->checkInManual($request->user(), $activity, $enrollment);
        } catch (EnrollmentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Asistencia registrada: {$registro->user->name}.");
    }

    /** Solo el responsable asignado y los administradores pasan lista de una actividad. */
    private function autorizar(Request $request, Activity $activity): void
    {
        abort_unless(
            $activity->canTakeAttendance($request->user()),
            403,
            'Solo el responsable asignado a esta actividad puede pasar lista.',
        );
    }

    private function exito(Enrollment $enrollment): array
    {
        return [
            'ok' => true,
            'already' => $enrollment->wasAlreadyAttended,
            'message' => $enrollment->wasAlreadyAttended
                ? "{$enrollment->user->name} ya tenía su asistencia registrada."
                : "Asistencia registrada: {$enrollment->user->name}.",
            'enrollment_id' => $enrollment->id,
            'student' => [
                'name' => $enrollment->user->name,
                'matricula' => $enrollment->user->matricula,
            ],
            'checkin_time' => $enrollment->checkin_time?->format('H:i'),
        ];
    }
}
