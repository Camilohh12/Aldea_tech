<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\LeeFiltros;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ResponsibleController extends Controller
{
    use LeeFiltros;

    // Rol que identifica a los responsables (migración create_responsable_role)
    private const ROLE = 'responsable';

    // Mostrar todos los responsables (con búsqueda por nombre o correo)
    public function index(Request $request)
    {
        $search = $this->filtroTexto($request, 'q');

        $responsibles = User::role(self::ROLE)
            ->with('roles')
            ->select('users.*')
            ->selectSub($this->activitiesCountQuery(), 'activities_count')
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
        $totalResponsibles = User::role(self::ROLE)->count();
        $withActivities = User::role(self::ROLE)
            ->whereIn('id', Activity::whereNotNull('responsible_id')->select('responsible_id'))
            ->count();

        return view('admin.responsibles.index', compact(
            'responsibles', 'search', 'totalResponsibles', 'withActivities'
        ));
    }

    // Mostrar formulario para registrar responsable
    public function create()
    {
        return view('admin.responsibles.create');
    }

    // Crear la cuenta del responsable y asignarle el rol
    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $user = User::create([
            'name' => $validated['name'],
            'email' => Str::lower($validated['email']),
            'password' => $validated['password'],
        ]);

        // La cuenta la crea el administrador, así que el correo se da por verificado
        $user->markEmailAsVerified();
        $user->assignRole(self::ROLE);

        return redirect()
            ->route('admin.responsibles.index')
            ->with('success', "Responsable registrado. Comparte con {$user->name} su correo y la contraseña temporal.");
    }

    // Mostrar formulario para editar
    public function edit(User $responsible)
    {
        $this->ensureIsResponsible($responsible);

        // Actividades asignadas, para mostrarlas debajo del formulario
        $activities = Activity::with('room')
            ->where('responsible_id', $responsible->id)
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return view('admin.responsibles.edit', compact('responsible', 'activities'));
    }

    // Actualizar datos (la contraseña solo cambia si se escribe una nueva)
    public function update(Request $request, User $responsible)
    {
        $this->ensureIsResponsible($responsible);

        $validated = $request->validate($this->rules($responsible), $this->messages());

        $data = [
            'name' => $validated['name'],
            'email' => Str::lower($validated['email']),
        ];

        if (! empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $responsible->update($data);

        return redirect()
            ->route('admin.responsibles.index')
            ->with('success', 'Responsable actualizado correctamente.');
    }

    // Eliminar responsable
    public function destroy(User $responsible)
    {
        $this->ensureIsResponsible($responsible);

        // Sus actividades quedarían sin responsable (responsible_id = NULL)
        if (Activity::where('responsible_id', $responsible->id)->exists()) {
            return redirect()
                ->route('admin.responsibles.index')
                ->with('error', "No se puede eliminar a «{$responsible->name}» porque tiene actividades asignadas. Asígnalas a otro responsable primero.");
        }

        // Si la cuenta tiene otros roles (por ejemplo, docente), solo se quita
        // el rol de responsable para no borrar su acceso al resto del sistema
        if ($responsible->roles->count() > 1) {
            $responsible->removeRole(self::ROLE);

            return redirect()
                ->route('admin.responsibles.index')
                ->with('success', "Se quitó el rol de responsable a «{$responsible->name}». Su cuenta se conserva porque tiene otros roles.");
        }

        $responsible->delete();

        return redirect()
            ->route('admin.responsibles.index')
            ->with('success', 'Responsable eliminado correctamente.');
    }

    // Solo se gestionan usuarios con el rol de responsable
    private function ensureIsResponsible(User $user): void
    {
        abort_unless($user->hasRole(self::ROLE), 404);
    }

    // Subconsulta: actividades donde el usuario es responsable
    private function activitiesCountQuery()
    {
        return Activity::selectRaw('count(*)')
            ->whereColumn('activities.responsible_id', 'users.id');
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
            'password.required' => 'La contraseña temporal es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }
}
