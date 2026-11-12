<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Grupo del alumno con la nomenclatura de la coordinación: prefijo de la
 * carrera, cuatrimestre y número de grupo, p. ej. TIID_01_02 (TIID, 1er
 * cuatrimestre, grupo 2). El alumno lo elige de una lista; también puede
 * elegir «Irregular» u «Otro» y entonces cuenta en el grupo de su tutor.
 * Los prefijos están en config/aldea.php (prefijos_grupo).
 */
final class Grupo
{
    /** Clave de la fila para quienes no tienen grupo (en la URL y en los reportes). */
    public const SIN_GRUPO = 'sin-grupo';

    /** Formato del código: letras_dos dígitos_dos dígitos. */
    public const PATRON = '[A-Za-z]+_[0-9]{2}_[0-9]{2}';

    public const IRREGULAR = 'Irregular';

    public const OTRO = 'Otro';

    /** Opciones que no son un grupo: el alumno cuenta en el grupo de su tutor. */
    public const SIN_GRUPO_PROPIO = [self::IRREGULAR, self::OTRO];

    /** ¿Es un código de grupo (TIID_01_02) y no «Irregular»/«Otro»? */
    public static function esCodigo(?string $grupo): bool
    {
        return $grupo !== null && preg_match('/^'.self::PATRON.'$/', $grupo) === 1;
    }

    /**
     * Grupos que el alumno puede elegir: los de config/aldea.php (grupos), en el
     * orden de la coordinación, y después los que el administrador asignó a los
     * tutores y no están en esa lista.
     *
     * @return list<string>
     */
    public static function codigos(): array
    {
        $deTutores = User::query()
            ->whereNotNull('grupos_tutoria')
            ->pluck('grupos_tutoria')
            ->flatMap(fn (string $grupos) => preg_split('/\s*,\s*/', $grupos));

        $limpiar = fn (iterable $codigos) => collect($codigos)
            ->map(fn ($codigo) => strtoupper(trim((string) $codigo)))
            ->filter(fn (string $codigo) => self::esCodigo($codigo));

        $oficiales = $limpiar(config('aldea.grupos', []));

        return $oficiales
            ->merge($limpiar($deTutores)->diff($oficiales)->sort())
            ->unique()
            ->values()
            ->all();
    }

    /** Lista completa del campo «Grupo»: los códigos y al final «Irregular» y «Otro». */
    public static function opciones(): array
    {
        return [...self::codigos(), ...self::SIN_GRUPO_PROPIO];
    }

    /** Código del grupo de cada tutor ({código: id del tutor}) para proponer al tutor en el registro. */
    public static function tutorPorGrupo(): array
    {
        return User::whereHas('roles', fn ($query) => $query->where('name', 'tutor'))
            ->whereNotNull('grupos_tutoria')
            ->get(['id', 'grupos_tutoria'])
            ->flatMap(fn (User $tutor) => collect(preg_split('/\s*,\s*/', $tutor->grupos_tutoria))
                ->mapWithKeys(fn (string $codigo) => [strtoupper($codigo) => $tutor->id]))
            ->all();
    }

    /** Tutor que atiende un grupo (null si es «Irregular», «Otro» o nadie lo atiende). */
    public static function tutorDe(?string $grupo): ?int
    {
        return self::esCodigo($grupo) ? (self::tutorPorGrupo()[strtoupper($grupo)] ?? null) : null;
    }

    /**
     * Pone en la lista del tutor a los alumnos de sus grupos (los que se
     * registraron antes de que el tutor tuviera ese grupo). Devuelve cuántos.
     */
    public static function asignarAlumnos(User $tutor): int
    {
        $grupos = collect(preg_split('/\s*,\s*/', (string) $tutor->grupos_tutoria))->filter()->values();

        if ($grupos->isEmpty()) {
            return 0;
        }

        return User::whereHas('roles', fn ($query) => $query->where('name', 'alumno'))
            ->whereIn('grupo', $grupos)
            ->where(fn ($query) => $query->whereNull('tutor_id')->orWhere('tutor_id', '!=', $tutor->id))
            ->update(['tutor_id' => $tutor->id]);
    }

    /** «Irregular» u «Otro» en el grupo (o, en cuentas anteriores, en el cuatrimestre). */
    public static function usaElDelTutor(?string $grupo, ?string $cuatrimestre = null): bool
    {
        if (in_array($grupo, self::SIN_GRUPO_PROPIO, true)) {
            return true;
        }

        return ! self::esCodigo($grupo)
            && $cuatrimestre !== null && $cuatrimestre !== '' && ! ctype_digit($cuatrimestre);
    }

    /**
     * Partes del código: ['carrera' => ..., 'cuatrimestre' => 1, 'numero' => 2].
     * null si el código no tiene el formato o su prefijo no es de ninguna carrera.
     */
    public static function partes(?string $codigo): ?array
    {
        if ($codigo === null || ! preg_match('/^([A-Za-z]+)_([0-9]{2})_([0-9]{2})$/', $codigo, $m)) {
            return null;
        }

        $carrera = array_search(strtoupper($m[1]), array_map('strtoupper', config('aldea.prefijos_grupo')), true);

        return $carrera === false ? null : [
            'carrera' => $carrera,
            'cuatrimestre' => (int) $m[2],
            'numero' => (int) $m[3],
        ];
    }

    /** «Grupo 2 · 1° cuatrimestre» */
    public static function descripcion(?string $codigo): string
    {
        $partes = self::partes($codigo);

        return $partes ? "Grupo {$partes['numero']} · {$partes['cuatrimestre']}° cuatrimestre" : 'Sin grupo';
    }

    /**
     * Grupo en el que cuenta cada alumno: el suyo o, si eligió «Irregular» u
     * «Otro», el de su tutor: el que más se repite entre sus tutorados y, si
     * ninguno tiene, el primero de los grupos que atiende el tutor.
     *
     * @param  Collection  $alumnos  objetos con id, grupo, cuatrimestre y tutor_id
     * @return Collection<int, array{grupo: ?string, por_tutor: bool}> por id de alumno
     */
    public static function resolver(Collection $alumnos): Collection
    {
        $grupoDelTutor = $alumnos
            ->filter(fn ($alumno) => self::esCodigo($alumno->grupo) && $alumno->tutor_id)
            ->groupBy('tutor_id')
            ->map(fn (Collection $tutorados) => $tutorados->countBy('grupo')
                ->sortKeys()          // en empate gana el código menor, siempre el mismo
                ->sortDesc()
                ->keys()
                ->first());

        $gruposQueAtiende = User::query()
            ->whereIn('id', $alumnos->pluck('tutor_id')->filter()->unique())
            ->whereNotNull('grupos_tutoria')
            ->pluck('grupos_tutoria', 'id')
            ->map(fn (string $grupos) => strtoupper(trim(explode(',', $grupos)[0])));

        return $alumnos->mapWithKeys(function ($alumno) use ($grupoDelTutor, $gruposQueAtiende) {
            if (self::esCodigo($alumno->grupo)) {
                return [$alumno->id => ['grupo' => $alumno->grupo, 'por_tutor' => false]];
            }

            $delTutor = self::usaElDelTutor($alumno->grupo, $alumno->cuatrimestre) && $alumno->tutor_id
                ? ($grupoDelTutor->get($alumno->tutor_id) ?? $gruposQueAtiende->get($alumno->tutor_id))
                : null;

            return [$alumno->id => ['grupo' => $delTutor, 'por_tutor' => $delTutor !== null]];
        });
    }
}
