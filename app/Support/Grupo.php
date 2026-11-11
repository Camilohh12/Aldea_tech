<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Código de grupo del alumno: prefijo de la carrera, número de grupo y
 * cuatrimestre, p. ej. sftw_01_09 (Software, grupo 1, 9° cuatrimestre).
 * Los prefijos están en config/aldea.php (prefijos_grupo).
 */
final class Grupo
{
    /** Clave de la fila para quienes no tienen grupo (en la URL y en los reportes). */
    public const SIN_GRUPO = 'sin-grupo';

    /** Formato del código: letras_dos dígitos_dos dígitos. */
    public const PATRON = '[a-z]+_[0-9]{2}_[0-9]{2}';

    /** Solo los cuatrimestres numéricos llevan grupo; «Irregular» y «Otro» usan el de su tutor. */
    public static function llevaGrupo(?string $cuatrimestre): bool
    {
        return $cuatrimestre !== null && ctype_digit($cuatrimestre);
    }

    /** «Irregular» u «Otro»: no capturan grupo y cuentan en el de su tutor (sin cuatrimestre no aplica). */
    public static function usaElDelTutor(?string $cuatrimestre): bool
    {
        return $cuatrimestre !== null && $cuatrimestre !== '' && ! self::llevaGrupo($cuatrimestre);
    }

    /** Arma el código; null si falta algún dato o la carrera no tiene prefijo. */
    public static function codigo(?string $carrera, ?string $cuatrimestre, int|string|null $numero): ?string
    {
        $prefijo = config('aldea.prefijos_grupo')[$carrera] ?? null;

        if (! $prefijo || ! self::llevaGrupo($cuatrimestre) || ! ctype_digit((string) $numero) || (int) $numero < 1) {
            return null;
        }

        return sprintf('%s_%02d_%02d', $prefijo, (int) $numero, (int) $cuatrimestre);
    }

    /**
     * Partes del código: ['carrera' => ..., 'numero' => 1, 'cuatrimestre' => 9].
     * null si el código no tiene el formato o su prefijo no es de ninguna carrera.
     */
    public static function partes(?string $codigo): ?array
    {
        if ($codigo === null || ! preg_match('/^([a-z]+)_([0-9]{2})_([0-9]{2})$/', $codigo, $m)) {
            return null;
        }

        $carrera = array_search($m[1], config('aldea.prefijos_grupo'), true);

        return $carrera === false ? null : [
            'carrera' => $carrera,
            'numero' => (int) $m[2],
            'cuatrimestre' => (int) $m[3],
        ];
    }

    /** Número que el alumno escribió (para volver a mostrarlo en «Mi perfil»). */
    public static function numero(?string $codigo): ?int
    {
        return self::partes($codigo)['numero'] ?? null;
    }

    /** «Grupo 1 · 9° cuatrimestre» */
    public static function descripcion(?string $codigo): string
    {
        $partes = self::partes($codigo);

        return $partes ? "Grupo {$partes['numero']} · {$partes['cuatrimestre']}° cuatrimestre" : 'Sin grupo';
    }

    /**
     * Grupo en el que cuenta cada alumno: el suyo o, si es «Irregular» u «Otro»,
     * el de su tutor (el grupo que más se repite entre los tutorados que sí lo tienen).
     *
     * @param  Collection  $alumnos  objetos con id, grupo, cuatrimestre y tutor_id
     * @return Collection<int, array{grupo: ?string, por_tutor: bool}> por id de alumno
     */
    public static function resolver(Collection $alumnos): Collection
    {
        $grupoDelTutor = $alumnos
            ->filter(fn ($alumno) => $alumno->grupo && $alumno->tutor_id)
            ->groupBy('tutor_id')
            ->map(fn (Collection $tutorados) => $tutorados->countBy('grupo')
                ->sortKeys()          // en empate gana el código menor, siempre el mismo
                ->sortDesc()
                ->keys()
                ->first());

        return $alumnos->mapWithKeys(function ($alumno) use ($grupoDelTutor) {
            if ($alumno->grupo) {
                return [$alumno->id => ['grupo' => $alumno->grupo, 'por_tutor' => false]];
            }

            $delTutor = self::usaElDelTutor($alumno->cuatrimestre) && $alumno->tutor_id
                ? $grupoDelTutor->get($alumno->tutor_id)
                : null;

            return [$alumno->id => ['grupo' => $delTutor, 'por_tutor' => $delTutor !== null]];
        });
    }
}
