<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/**
 * Reglas de los datos del alumno, compartidas por el registro y «Mi perfil»
 * para que las dos pantallas acepten exactamente lo mismo.
 */
class ReglasAlumno
{
    /** Letras (con acentos), espacios, puntos, apóstrofos y guiones. */
    public const PATRON_NOMBRE = "/^[\pL\pM\s.'\-]+$/u";

    /** Caracteres máximos del nombre completo. */
    public const NOMBRE_MAX = 50;

    /** Caracteres mínimos de la contraseña (además, al menos una mayúscula). */
    public const CONTRASENA_MIN = 6;

    public static function matricula(): array
    {
        return ['required', 'digits:'.config('aldea.matricula_digitos')];
    }

    public static function nombre(): array
    {
        return ['required', 'string', 'max:'.self::NOMBRE_MAX, 'regex:'.self::PATRON_NOMBRE];
    }

    /** Correo institucional de alumno: exactamente @micorreo.upp.edu.mx (config aldea.dominio_correo). */
    public static function correoInstitucional(): string
    {
        $dominio = preg_quote(config('aldea.dominio_correo'), '/');

        return "regex:/@{$dominio}$/";
    }

    /**
     * Contraseña nueva: mínimo 6 caracteres con al menos una mayúscula.
     * La usan el registro, «Recuperar contraseña» y el cambio en «Mi perfil».
     */
    public static function contrasena(): array
    {
        return ['required', 'string', 'min:'.self::CONTRASENA_MIN, 'regex:/\p{Lu}/u', 'confirmed'];
    }

    public static function carrera(): array
    {
        return ['required', 'string', Rule::in(config('aldea.carreras'))];
    }

    public static function cuatrimestre(): array
    {
        return ['required', Rule::in(config('aldea.cuatrimestres'))];
    }

    /**
     * Grupo de la lista (TIID_01_02…) o «Irregular» / «Otro». Si el cuatrimestre
     * ya es «Irregular» u «Otro» puede quedar vacío: cuenta en el grupo de su tutor.
     */
    public static function grupo(mixed $cuatrimestre = null): array
    {
        $cuatrimestre = is_scalar($cuatrimestre) ? (string) $cuatrimestre : '';
        $opcional = $cuatrimestre !== '' && ! ctype_digit($cuatrimestre);

        return [$opcional ? 'nullable' : 'required', 'string', Rule::in(Grupo::opciones())];
    }

    /** «Irregular» en el cuatrimestre o en el grupo marca al alumno como irregular. */
    public static function estatus(?string $cuatrimestre, ?string $grupo = null): string
    {
        return $cuatrimestre === 'Irregular' || $grupo === Grupo::IRREGULAR ? 'irregular' : 'regular';
    }

    public static function mensajes(): array
    {
        $digitos = config('aldea.matricula_digitos');

        return [
            'matricula.required' => 'Escribe tu matrícula.',
            'matricula.digits' => "La matrícula debe tener {$digitos} dígitos, sin espacios ni letras.",
            'name.regex' => 'El nombre solo puede tener letras, espacios, puntos, apóstrofos y guiones.',
            'carrera.required' => 'Selecciona tu carrera.',
            'carrera.in' => 'Selecciona una carrera de la lista.',
            'cuatrimestre.required' => 'Selecciona tu cuatrimestre.',
            'cuatrimestre.in' => 'Selecciona un cuatrimestre de la lista.',
            'grupo.required' => 'Selecciona tu grupo.',
            'grupo.in' => 'Selecciona tu grupo de la lista.',
            'name.max' => 'El nombre puede tener máximo '.self::NOMBRE_MAX.' caracteres.',
            'email.regex' => 'Usa tu correo institucional (@'.config('aldea.dominio_correo').').',
            ...self::mensajesContrasena(),
        ];
    }

    public static function mensajesContrasena(): array
    {
        return [
            'password.required' => 'Escribe una contraseña.',
            'password.min' => 'La contraseña debe tener al menos '.self::CONTRASENA_MIN.' caracteres.',
            'password.regex' => 'La contraseña debe incluir al menos una letra mayúscula.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ];
    }

    /** Patrón para el atributo pattern del campo de correo (validación en el navegador). */
    public static function patronCorreoHtml(): string
    {
        $dominio = str_replace('.', '\.', config('aldea.dominio_correo'));

        return "[^@\\s]+@{$dominio}";
    }

    /** Patrón para el atributo pattern de la contraseña: 6 o más caracteres con una mayúscula. */
    public static function patronContrasenaHtml(): string
    {
        return '(?=.*\p{Lu}).{'.self::CONTRASENA_MIN.',}';
    }

    /** Patrón para el atributo pattern del campo de nombre. */
    public static function patronNombreHtml(): string
    {
        return "[\\p{L}\\p{M}\\s.'\\-]+";
    }
}
