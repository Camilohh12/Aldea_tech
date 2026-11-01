<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
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
        // Tutores para que el alumno elija al suyo (agrupados por carrera en la vista)
        return view('auth.register', [
            'tutores' => $this->tutores()->orderBy('name')->get(['id', 'name', 'carrera']),
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
        $dominio = preg_quote(config('aldea.dominio_correo'), '/');

        // El tutor es obligatorio en cuanto la coordinación haya registrado tutores
        $hayTutores = $this->tutores()->exists();

        $request->validate([
            'matricula' => ['required', 'digits:9', 'unique:'.User::class],
            'name' => ['required', 'string', 'max:255'],
            'carrera' => ['required', 'string', Rule::in(config('aldea.carreras'))],
            'cuatrimestre' => ['required', Rule::in(config('aldea.cuatrimestres'))],
            'tutor_id' => ['bail', $hayTutores ? 'required' : 'nullable', 'integer', function ($attribute, $value, $fail) {
                if (! $this->tutores()->whereKey($value)->exists()) {
                    $fail('Selecciona a tu tutor de la lista.');
                }
            }],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class,
                "regex:/@([a-z0-9-]+\\.)*{$dominio}$/"],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'acepto' => ['accepted'],
        ], [
            'matricula.digits' => 'La matrícula debe tener 9 dígitos.',
            'matricula.unique' => 'Esa matrícula ya tiene una cuenta. Inicia sesión.',
            'email.regex' => 'Usa tu correo institucional (@'.config('aldea.dominio_correo').').',
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
            'estatus' => $request->cuatrimestre === 'Irregular' ? 'irregular' : 'regular',
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
