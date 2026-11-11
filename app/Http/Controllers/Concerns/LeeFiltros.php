<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Lectura segura de los filtros que llegan en la URL (?q=, ?tipo=, ?dia=...).
 *
 * Un valor con otro formato (una lista como ?q[]=x, texto en un id, una fecha
 * inexistente) se ignora en lugar de provocar un error 500. Las consultas
 * siguen usando parámetros enlazados de Eloquent; esto solo normaliza la entrada.
 */
trait LeeFiltros
{
    /** Texto de búsqueda sin espacios sobrantes y con un máximo de caracteres. */
    protected function filtroTexto(Request $request, string $clave, int $maximo = 100): string
    {
        $valor = $request->input($clave);

        return is_string($valor) ? Str::limit(trim($valor), $maximo, '') : '';
    }

    /** Identificador numérico positivo, o null. */
    protected function filtroEntero(Request $request, string $clave): ?int
    {
        $valor = $request->input($clave);

        return is_string($valor) && ctype_digit($valor) && (int) $valor > 0 ? (int) $valor : null;
    }

    /** Fecha real en formato AAAA-MM-DD, o null. */
    protected function filtroFecha(Request $request, string $clave): ?string
    {
        $valor = $request->input($clave);

        if (! is_string($valor) || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valor, $partes)) {
            return null;
        }

        return checkdate((int) $partes[2], (int) $partes[3], (int) $partes[1]) ? $valor : null;
    }

    /** Uno de los valores permitidos; si no, el valor por defecto. */
    protected function filtroOpcion(Request $request, string $clave, array $opciones, ?string $porDefecto = null): ?string
    {
        $valor = $request->input($clave);

        return is_string($valor) && in_array($valor, $opciones, true) ? $valor : $porDefecto;
    }
}
