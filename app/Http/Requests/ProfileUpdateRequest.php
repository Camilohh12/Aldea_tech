<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\ReglasAlumno;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /** El correo se guarda en minúsculas, igual que en el registro. */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower($this->input('email'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];

        // El alumno edita sus datos académicos con las mismas reglas del registro
        if ($this->esAlumno()) {
            $rules['name'] = ReglasAlumno::nombre();
            // Solo al cambiarlo: las cuentas creadas antes (o por seeders) siguen pudiendo guardar
            if ($this->input('email') !== $this->user()->email) {
                $rules['email'][] = ReglasAlumno::correoInstitucional();
            }
            $rules['carrera'] = ReglasAlumno::carrera();
            $rules['cuatrimestre'] = ReglasAlumno::cuatrimestre();
            $rules['grupo'] = ReglasAlumno::grupo($this->input('cuatrimestre'));
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            ...ReglasAlumno::mensajes(),
            'email.unique' => 'Ese correo ya lo usa otra cuenta.',
        ];
    }

    public function esAlumno(): bool
    {
        return $this->user()->hasRole('alumno');
    }
}
