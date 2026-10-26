<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Room;
use App\Models\Speaker;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ActivityController extends Controller
{
    // Roles que pueden quedar como responsables (pasar lista):
    // de preferencia un responsable; los docentes también pueden hacerlo.
    private const RESPONSIBLE_ROLES = ['responsable', 'docente'];

    // Mostrar la programación (con búsqueda y filtros)
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $typeId = $request->query('tipo');
        $day = $request->query('dia');

        $activities = Activity::with(['activityType', 'room', 'responsible', 'speakers'])
            ->select('activities.*')
            ->selectSub($this->enrolledCountQuery(), 'enrolled_count')
            ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->when($typeId, fn ($query) => $query->where('activity_type_id', $typeId))
            ->when($day, fn ($query) => $query->whereDate('date', $day))
            ->orderBy('date')
            ->orderBy('start_time')
            ->paginate(10)
            ->withQueryString();

        // Datos para las tarjetas de resumen
        $totalActivities = Activity::count();
        $totalSeats = (int) Activity::sum('capacity');
        $speakersInvolved = Speaker::has('activities')->count();

        $types = ActivityType::orderBy('name')->get();
        $eventDays = $this->eventDays();

        return view('admin.activities.index', compact(
            'activities', 'search', 'typeId', 'day', 'types', 'eventDays',
            'totalActivities', 'totalSeats', 'speakersInvolved'
        ));
    }

    // Mostrar formulario para registrar actividad
    public function create()
    {
        return view('admin.activities.create', $this->formData());
    }

    // Guardar actividad nueva con sus ponentes
    public function store(Request $request)
    {
        $validated = $this->validateActivity($request);

        DB::transaction(function () use ($validated) {
            $activity = Activity::create(
                collect($validated)->except('speakers')->all()
            );

            $activity->speakers()->sync($validated['speakers']);
        });

        return redirect()
            ->route('admin.activities.index')
            ->with('success', 'Actividad registrada correctamente.');
    }

    // Mostrar formulario para editar
    public function edit(Activity $activity)
    {
        $activity->load('speakers');

        return view('admin.activities.edit', array_merge(
            $this->formData(),
            compact('activity')
        ));
    }

    // Actualizar actividad y sus ponentes
    public function update(Request $request, Activity $activity)
    {
        $validated = $this->validateActivity($request, $activity);

        DB::transaction(function () use ($activity, $validated) {
            $activity->update(
                collect($validated)->except('speakers')->all()
            );

            $activity->speakers()->sync($validated['speakers']);
        });

        return redirect()
            ->route('admin.activities.index')
            ->with('success', 'Actividad actualizada correctamente.');
    }

    // Eliminar actividad
    public function destroy(Activity $activity)
    {
        // Las inscripciones se borrarían en cascada, así que no se permite
        $enrolled = DB::table('enrollments')
            ->where('activity_id', $activity->id)
            ->count();

        if ($enrolled > 0) {
            return redirect()
                ->route('admin.activities.index')
                ->with('error', "No se puede eliminar «{$activity->title}» porque tiene alumnos inscritos.");
        }

        // La relación con ponentes (activity_speaker) se borra en cascada
        $activity->delete();

        return redirect()
            ->route('admin.activities.index')
            ->with('success', 'Actividad eliminada correctamente.');
    }

    // Catálogos que necesitan los formularios de crear y editar
    private function formData(): array
    {
        return [
            'types' => ActivityType::orderBy('name')->get(),
            'rooms' => Room::orderBy('name')->get(),
            'speakers' => Speaker::orderBy('name')->get(),
            'responsibles' => User::role(self::RESPONSIBLE_ROLES)
                ->with('roles')
                ->orderBy('name')
                ->get(),
            'eventDays' => $this->eventDays(),
        ];
    }

    // Valida los campos y las reglas de programación
    private function validateActivity(Request $request, ?Activity $activity = null): array
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'activity_type_id' => ['required', 'integer', Rule::exists('activity_types', 'id')],
            'room_id' => ['required', 'integer', Rule::exists('rooms', 'id')],
            'date' => [
                'required', 'date_format:Y-m-d',
                'after_or_equal:' . config('aldea.event.start_date'),
                'before_or_equal:' . config('aldea.event.end_date'),
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'capacity' => ['required', 'integer', 'min:1', 'max:10000'],
            'duration_hours' => ['required', 'numeric', 'min:0.5', 'max:24'],
            'responsible_id' => [
                'required', 'integer',
                function ($attribute, $value, $fail) {
                    if (! User::role(self::RESPONSIBLE_ROLES)->whereKey($value)->exists()) {
                        $fail('El usuario seleccionado no es responsable ni docente.');
                    }
                },
            ],
            'speakers' => ['required', 'array', 'min:1'],
            'speakers.*' => ['integer', 'distinct', Rule::exists('speakers', 'id')],
        ], $this->messages());

        // Reglas que dependen de otras actividades (solo si lo básico es válido)
        $validator->after(function ($validator) use ($activity) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            foreach ($this->scheduleErrors($validator->getData(), $activity) as $field => $message) {
                $validator->errors()->add($field, $message);
            }
        });

        return $validator->validate();
    }

    // Cupo, horas y choques de horario. Devuelve [campo => mensaje].
    private function scheduleErrors(array $data, ?Activity $activity): array
    {
        $errors = [];
        $room = Room::find($data['room_id']);
        $start = $data['start_time'] . ':00';
        $end = $data['end_time'] . ':00';

        // 1. El cupo no puede superar la capacidad del aula
        if ((int) $data['capacity'] > $room->capacity) {
            $errors['capacity'] = "El cupo ({$data['capacity']}) supera la capacidad del aula «{$room->name}» ({$room->capacity} personas).";
        }

        // 2. Al editar, el cupo no puede quedar por debajo de los inscritos
        if ($activity && ! isset($errors['capacity'])) {
            $enrolled = DB::table('enrollments')
                ->where('activity_id', $activity->id)
                ->where('status', 'registered')
                ->count();

            if ((int) $data['capacity'] < $enrolled) {
                $errors['capacity'] = "Ya hay {$enrolled} alumnos inscritos; el cupo no puede ser menor.";
            }
        }

        // 3. Las horas acreditables no pueden superar la duración de la sesión
        $sessionHours = Carbon::parse($start)->diffInMinutes(Carbon::parse($end)) / 60;

        if ((float) $data['duration_hours'] > $sessionHours) {
            $errors['duration_hours'] = 'Las horas acreditables no pueden ser más que la duración de la sesión ('
                . rtrim(rtrim(number_format($sessionHours, 2), '0'), '.') . ' h).';
        }

        // Actividades del mismo día que se cruzan con este horario.
        // Si una termina a las 12:00 y otra empieza a las 12:00, no se cruzan.
        $overlapping = Activity::with('speakers')
            ->whereDate('date', $data['date'])
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->when($activity, fn ($query) => $query->whereKeyNot($activity->id))
            ->get();

        // 4. La misma aula no puede tener dos actividades a la vez
        if ($clash = $overlapping->firstWhere('room_id', $data['room_id'])) {
            $errors['room_id'] = "El aula «{$room->name}» ya está ocupada por «{$clash->title}» ({$clash->time_range}).";
        }

        // 5. Un ponente no puede estar en dos actividades a la vez
        foreach ($overlapping as $other) {
            $shared = $other->speakers->whereIn('id', $data['speakers'])->first();

            if ($shared) {
                $errors['speakers'] = "{$shared->name} ya imparte «{$other->title}» ({$other->time_range}) ese día.";
                break;
            }
        }

        // 6. El responsable no puede supervisar dos actividades a la vez
        if ($clash = $overlapping->firstWhere('responsible_id', $data['responsible_id'])) {
            $errors['responsible_id'] = "Este responsable ya supervisa «{$clash->title}» ({$clash->time_range}) ese día.";
        }

        return $errors;
    }

    // Subconsulta: alumnos inscritos (no cancelados) por actividad
    private function enrolledCountQuery()
    {
        return DB::table('enrollments')
            ->selectRaw('count(*)')
            ->whereColumn('enrollments.activity_id', 'activities.id')
            ->where('status', 'registered');
    }

    // Días del evento para los selectores: ['2026-11-24' => 'Martes 24 de noviembre', ...]
    private function eventDays(): array
    {
        $days = [];
        $period = CarbonPeriod::create(
            config('aldea.event.start_date'),
            config('aldea.event.end_date')
        );

        foreach ($period as $date) {
            $days[$date->format('Y-m-d')] = ucfirst(
                $date->locale('es')->translatedFormat('l j \d\e F')
            );
        }

        return $days;
    }

    // Mensajes de validación en español
    private function messages(): array
    {
        $start = Carbon::parse(config('aldea.event.start_date'))->locale('es');
        $end = Carbon::parse(config('aldea.event.end_date'))->locale('es');
        $dateRange = 'La fecha debe estar entre el ' . $start->translatedFormat('j')
            . ' y el ' . $end->translatedFormat('j \d\e F \d\e Y') . '.';

        return [
            'title.required' => 'El nombre de la actividad es obligatorio.',
            'title.max' => 'El nombre no puede tener más de 255 caracteres.',
            'description.max' => 'La descripción no puede tener más de 5,000 caracteres.',
            'activity_type_id.required' => 'Selecciona el tipo de actividad.',
            'activity_type_id.exists' => 'El tipo de actividad no es válido.',
            'room_id.required' => 'Selecciona el aula.',
            'room_id.exists' => 'El aula seleccionada no existe.',
            'date.required' => 'Selecciona la fecha.',
            'date.date_format' => 'La fecha no es válida.',
            'date.after_or_equal' => $dateRange,
            'date.before_or_equal' => $dateRange,
            'start_time.required' => 'Indica la hora de inicio.',
            'start_time.date_format' => 'La hora de inicio no es válida.',
            'end_time.required' => 'Indica la hora de finalización.',
            'end_time.date_format' => 'La hora de finalización no es válida.',
            'end_time.after' => 'La hora de finalización debe ser posterior a la de inicio.',
            'capacity.required' => 'Indica el cupo máximo.',
            'capacity.integer' => 'El cupo debe ser un número entero.',
            'capacity.min' => 'El cupo debe ser de al menos 1 persona.',
            'capacity.max' => 'El cupo no puede ser mayor a 10,000 personas.',
            'duration_hours.required' => 'Indica las horas acreditables.',
            'duration_hours.numeric' => 'Las horas acreditables deben ser un número.',
            'duration_hours.min' => 'Las horas acreditables deben ser al menos 0.5.',
            'duration_hours.max' => 'Las horas acreditables no pueden ser más de 24.',
            'responsible_id.required' => 'Selecciona al responsable de la actividad.',
            'responsible_id.integer' => 'El responsable seleccionado no es válido.',
            'speakers.required' => 'Selecciona al menos un ponente.',
            'speakers.min' => 'Selecciona al menos un ponente.',
            'speakers.*.distinct' => 'Seleccionaste un ponente repetido.',
            'speakers.*.exists' => 'Uno de los ponentes seleccionados no existe.',
        ];
    }
}
