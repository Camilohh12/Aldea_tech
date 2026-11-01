<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TutorController extends Controller
{
    // Rol que identifica a los tutores académicos
    private const ROLE = 'tutor';

    // Mostrar todos los tutores (con búsqueda por nombre o correo)
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $tutors = User::role(self::ROLE)
            ->with('roles')
            ->withCount(['tutorados' => fn ($query) => $query->role('alumno')])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        // Datos para las tarjetas de resumen
        $totalTutors = User::role(self::ROLE)->count();
        $withTutor = User::role('alumno')->whereNotNull('tutor_id')->count();
        $withoutTutor = User::role('alumno')->whereNull('tutor_id')->count();

        return view('admin.tutors.index', compact(
            'tutors', 'search', 'totalTutors', 'withTutor', 'withoutTutor'
        ));
    }

    // Mostrar formulario para registrar tutor
    public function create()
    {
        return view('admin.tutors.create');
    }

    // Crear la cuenta del tutor y asignarle el rol
    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $user = User::create([
            'name' => $validated['name'],
            'email' => Str::lower($validated['email']),
            'carrera' => $validated['carrera'] ?? null,
            'password' => $validated['password'],
        ]);

        // La cuenta la crea el administrador, así que el correo se da por verificado
        $user->markEmailAsVerified();
        $user->assignRole(self::ROLE);

        return redirect()
            ->route('admin.tutors.students', $user)
            ->with('success', "Tutor registrado. Ahora asígnale sus alumnos y comparte con {$user->name} su correo y la contraseña temporal.");
    }

    // Mostrar formulario para editar, con los alumnos que tiene asignados
    public function edit(User $tutor)
    {
        $this->ensureIsTutor($tutor);

        $students = $tutor->tutorados()
            ->role('alumno')
            ->orderBy('name')
            ->get();

        return view('admin.tutors.edit', compact('tutor', 'students'));
    }

    // Actualizar datos (la contraseña solo cambia si se escribe una nueva)
    public function update(Request $request, User $tutor)
    {
        $this->ensureIsTutor($tutor);

        $validated = $request->validate($this->rules($tutor), $this->messages());

        $data = [
            'name' => $validated['name'],
            'email' => Str::lower($validated['email']),
            'carrera' => $validated['carrera'] ?? null,
        ];

        if (! empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $tutor->update($data);

        return redirect()
            ->route('admin.tutors.index')
            ->with('success', 'Tutor actualizado correctamente.');
    }

    // Eliminar tutor: sus alumnos quedan sin tutor para reasignarlos
    public function destroy(User $tutor)
    {
        $this->ensureIsTutor($tutor);

        $released = User::where('tutor_id', $tutor->id)->update(['tutor_id' => null]);
        $note = match (true) {
            $released === 1 => ' Su alumno quedó sin tutor.',
            $released > 1 => " Sus {$released} alumnos quedaron sin tutor.",
            default => '',
        };

        // Si la cuenta tiene otros roles (por ejemplo, docente), solo se quita
        // el rol de tutor para no borrar su acceso al resto del sistema
        if ($tutor->roles->count() > 1) {
            $tutor->removeRole(self::ROLE);

            return redirect()
                ->route('admin.tutors.index')
                ->with('success', "Se quitó el rol de tutor a «{$tutor->name}». Su cuenta se conserva porque tiene otros roles.{$note}");
        }

        $tutor->delete();

        return redirect()
            ->route('admin.tutors.index')
            ->with('success', "Tutor eliminado correctamente.{$note}");
    }

    // Buscar alumnos para asignarlos al tutor (por nombre, carrera y cuatrimestre)
    public function students(Request $request, User $tutor)
    {
        $this->ensureIsTutor($tutor);

        $filters = $this->studentFilters($request);

        $students = $this->studentsQuery($tutor, $filters)
            ->with('tutor')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.tutors.students', [
            'tutor' => $tutor,
            'students' => $students,
            'filters' => $filters,
            'assignedCount' => $tutor->tutorados()->role('alumno')->count(),
        ]);
    }

    // Asignar al tutor los alumnos marcados o todos los que coinciden con el filtro
    public function assign(Request $request, User $tutor)
    {
        $this->ensureIsTutor($tutor);

        $request->validate([
            'alumnos' => ['array'],
            'alumnos.*' => ['integer'],
        ]);

        $filters = $this->studentFilters($request);

        $ids = $request->boolean('todos')
            ? $this->studentsQuery($tutor, $filters)->pluck('id')
            : User::role('alumno')->whereIn('id', $request->input('alumnos', []))->pluck('id');

        $back = redirect()->route('admin.tutors.students', [$tutor, ...array_filter($filters)]);

        if ($ids->isEmpty()) {
            return $back->with('error', 'Selecciona al menos un alumno para asignar.');
        }

        User::whereIn('id', $ids)->update(['tutor_id' => $tutor->id]);

        $count = $ids->count();

        return $back->with('success', $count === 1
            ? "Se asignó 1 alumno a {$tutor->name}."
            : "Se asignaron {$count} alumnos a {$tutor->name}.");
    }

    // Quitar un alumno al tutor (queda sin tutor)
    public function unassign(User $tutor, User $alumno)
    {
        $this->ensureIsTutor($tutor);
        abort_unless((int) $alumno->tutor_id === $tutor->id, 404);

        $alumno->update(['tutor_id' => null]);

        return back()->with('success', "{$alumno->name} ya no está asignado a {$tutor->name}.");
    }

    // Solo se gestionan usuarios con el rol de tutor
    private function ensureIsTutor(User $user): void
    {
        abort_unless($user->hasRole(self::ROLE), 404);
    }

    // Filtros de la búsqueda de alumnos; por defecto solo los que no tienen tutor
    private function studentFilters(Request $request): array
    {
        $carrera = $request->input('carrera');
        $cuatrimestre = $request->input('cuatrimestre');

        return [
            'q' => trim((string) $request->input('q', '')),
            'carrera' => in_array($carrera, config('aldea.carreras'), true) ? $carrera : null,
            'cuatrimestre' => in_array($cuatrimestre, config('aldea.cuatrimestres'), true) ? $cuatrimestre : null,
            'mostrar' => $request->input('mostrar') === 'todos' ? 'todos' : 'sin_tutor',
        ];
    }

    // Alumnos que todavía no son de este tutor y coinciden con los filtros
    private function studentsQuery(User $tutor, array $filters)
    {
        return User::role('alumno')
            ->where(fn ($query) => $query->whereNull('tutor_id')->orWhere('tutor_id', '!=', $tutor->id))
            ->when($filters['mostrar'] === 'sin_tutor', fn ($query) => $query->whereNull('tutor_id'))
            ->when($filters['carrera'], fn ($query, $carrera) => $query->where('carrera', $carrera))
            ->when($filters['cuatrimestre'], fn ($query, $cuatrimestre) => $query->where('cuatrimestre', $cuatrimestre))
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $query->where(function ($query) use ($filters) {
                    $query->where('name', 'like', "%{$filters['q']}%")
                        ->orWhere('matricula', 'like', "%{$filters['q']}%")
                        ->orWhere('email', 'like', "%{$filters['q']}%");
                });
            });
    }

    // Reglas de validación (al editar, la contraseña es opcional)
    private function rules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'carrera' => ['nullable', Rule::in(config('aldea.carreras'))],
            'password' => [
                $user ? 'nullable' : 'required',
                'string', 'min:8', 'confirmed',
            ],
        ];
    }

    // Mensajes de validación en español
    private function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Escribe un correo válido.',
            'email.max' => 'El correo no puede tener más de 255 caracteres.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'carrera.in' => 'Selecciona una carrera de la lista.',
            'password.required' => 'La contraseña temporal es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }
}
