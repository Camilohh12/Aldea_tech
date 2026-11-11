<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\LeeFiltros;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

// Cuentas de administrador: la coordinación puede crear otros
// administradores con el mismo acceso y su propio usuario
class AdministratorController extends Controller
{
    use LeeFiltros;

    // Roles con acceso al panel de administración
    private const PANEL_ROLES = ['admin', 'super_admin'];

    // Mostrar todos los administradores (con búsqueda por nombre o correo)
    public function index(Request $request)
    {
        $search = $this->filtroTexto($request, 'q');

        $administrators = User::role(self::PANEL_ROLES)
            ->with('roles')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.administrators.index', [
            'administrators' => $administrators,
            'search' => $search,
            'total' => User::role(self::PANEL_ROLES)->count(),
            'superAdmins' => User::role('super_admin')->count(),
        ]);
    }

    // Mostrar formulario para registrar administrador
    public function create()
    {
        return view('admin.administrators.create');
    }

    // Crear la cuenta y asignarle el rol de administrador
    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $user = User::create([
            'name' => $validated['name'],
            'email' => Str::lower($validated['email']),
            'password' => $validated['password'],
        ]);

        // La cuenta la crea otro administrador, así que el correo se da por verificado
        $user->markEmailAsVerified();
        $user->assignRole('admin');

        return redirect()
            ->route('admin.administrators.index')
            ->with('success', "Administrador registrado. Comparte con {$user->name} su correo y la contraseña temporal.");
    }

    // Mostrar formulario para editar
    public function edit(Request $request, User $administrator)
    {
        $this->ensureCanManage($request, $administrator);

        return view('admin.administrators.edit', [
            'administrator' => $administrator,
            'isSelf' => $request->user()->is($administrator),
        ]);
    }

    // Actualizar datos (la contraseña solo cambia si se escribe una nueva)
    public function update(Request $request, User $administrator)
    {
        $this->ensureCanManage($request, $administrator);

        $validated = $request->validate($this->rules($administrator), $this->messages());

        $data = [
            'name' => $validated['name'],
            'email' => Str::lower($validated['email']),
        ];

        if (! empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $administrator->update($data);

        return redirect()
            ->route('admin.administrators.index')
            ->with('success', 'Administrador actualizado correctamente.');
    }

    // Eliminar administrador
    public function destroy(Request $request, User $administrator)
    {
        $this->ensureCanManage($request, $administrator);

        // Nadie puede quitarse el acceso a sí mismo
        if ($request->user()->is($administrator)) {
            return redirect()
                ->route('admin.administrators.index')
                ->with('error', 'No puedes eliminar tu propia cuenta de administrador.');
        }

        // Los Super Admin no se eliminan desde el panel
        if ($administrator->hasRole('super_admin')) {
            return redirect()
                ->route('admin.administrators.index')
                ->with('error', "«{$administrator->name}» es Super Admin y no se puede eliminar desde aquí.");
        }

        // Siempre debe quedar al menos un administrador
        if (User::role(self::PANEL_ROLES)->count() <= 1) {
            return redirect()
                ->route('admin.administrators.index')
                ->with('error', 'Debe quedar al menos un administrador.');
        }

        // Si tiene otros roles (por ejemplo, docente), solo se quita el de administrador
        if ($administrator->roles->count() > 1) {
            $administrator->removeRole('admin');

            return redirect()
                ->route('admin.administrators.index')
                ->with('success', "Se quitó el acceso de administrador a «{$administrator->name}». Su cuenta se conserva porque tiene otros roles.");
        }

        // Sus actividades quedarían sin responsable (responsible_id = NULL)
        if (Activity::where('responsible_id', $administrator->id)->exists()) {
            return redirect()
                ->route('admin.administrators.index')
                ->with('error', "No se puede eliminar a «{$administrator->name}» porque es responsable de actividades.");
        }

        $administrator->delete();

        return redirect()
            ->route('admin.administrators.index')
            ->with('success', 'Administrador eliminado correctamente.');
    }

    // Solo se gestionan cuentas del panel; las de Super Admin, solo otro Super Admin
    private function ensureCanManage(Request $request, User $user): void
    {
        abort_unless($user->hasAnyRole(self::PANEL_ROLES), 404);

        abort_if(
            $user->hasRole('super_admin') && ! $request->user()->hasRole('super_admin'),
            403,
            'Solo un Super Admin puede modificar esta cuenta.'
        );
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
