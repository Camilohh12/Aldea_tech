<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Grupo;
use App\Support\ReglasAlumno;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        // Tutores para que el alumno elija al suyo (agrupados por carrera en la vista,
        // primero los que tienen grupo y en orden de grupo)
        return view('auth.register', [
            'grupos' => Grupo::opciones(),
            'tutorPorGrupo' => Grupo::tutorPorGrupo(),
            'tutores' => $this->tutores()
                ->orderByRaw('grupos_tutoria IS NULL')
                ->orderBy('grupos_tutoria')
                ->orderBy('name')
                ->get(['id', 'name', 'carrera', 'grupos_tutoria']),
        ]);
    }

    /** Cuentas con rol de tutor (sin fallar si el rol aún no existe). */
    private function tutores()
    {
        return User::whereHas('roles', fn ($query) => $query->where('name', 'tutor'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // El correo se guarda en minúsculas: «Ana@UPP.edu.mx» es el mismo que «ana@upp.edu.mx»
        if (is_string($request->input('email'))) {
            $request->merge(['email' => Str::lower($request->input('email'))]);
        }

        // El tutor es obligatorio en cuanto la coordinación haya registrado tutores
        $hayTutores = $this->tutores()->exists();

        $request->validate([
            'matricula' => [...ReglasAlumno::matricula(), 'unique:'.User::class],
            'name' => ReglasAlumno::nombre(),
            'carrera' => ReglasAlumno::carrera(),
            'cuatrimestre' => ReglasAlumno::cuatrimestre(),
            'grupo' => ReglasAlumno::grupo($request->input('cuatrimestre')),
            'tutor_id' => ['bail', $hayTutores ? 'required' : 'nullable', 'integer', function ($attribute, $value, $fail) {
                if (! $this->tutores()->whereKey($value)->exists()) {
                    $fail('Selecciona a tu tutor de la lista.');
                }
            }],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class,
                ReglasAlumno::correoInstitucional()],
            'password' => ReglasAlumno::contrasena(),
            'acepto' => ['accepted'],
        ], [
            ...ReglasAlumno::mensajes(),
            'matricula.unique' => 'Esa matrícula ya tiene una cuenta. Inicia sesión.',
            'email.unique' => 'Ese correo ya tiene una cuenta. Inicia sesión.',
            'tutor_id.required' => 'Selecciona a tu tutor académico.',
            'tutor_id.integer' => 'Selecciona a tu tutor de la lista.',
            'acepto.accepted' => 'Debes aceptar el reglamento y el aviso de privacidad.',
        ]);

        $user = User::create([
            'matricula' => $request->matricula,
            'name' => $request->name,
            'carrera' => $request->carrera,
            'cuatrimestre' => (string) $request->cuatrimestre,
            // Código de la lista (TIID_01_02) o «Irregular» / «Otro»
            'grupo' => $request->grupo ?: null,
            'estatus' => ReglasAlumno::estatus((string) $request->cuatrimestre, $request->grupo),
            'tutor_id' => $request->tutor_id ?: null,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // El registro público es solo para alumnos.
        $user->assignRole(Role::firstOrCreate(['name' => 'alumno']));

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false))->with(
            'success',
            '¡Bienvenido(a), tu cuenta quedó creada! La próxima vez solo inicia sesión con tu matrícula '.$user->matricula.' y tu contraseña.',
        );
    }
}
