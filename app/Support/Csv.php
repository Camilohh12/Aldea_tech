<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/** Descargas en CSV que Excel abre bien. */
final class Csv
{
    /**
     * @param  array<int, string>  $encabezados
     * @param  iterable<array<int, mixed>>  $filas
     */
    public static function descargar(string $archivo, array $encabezados, iterable $filas): StreamedResponse
    {
        return response()->streamDownload(function () use ($encabezados, $filas) {
            $out = fopen('php://output', 'w');

            // Marca UTF-8 para que Excel muestre bien los acentos
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $encabezados, ',', '"', '\\');

            foreach ($filas as $fila) {
                fputcsv($out, array_map(self::celda(...), $fila), ',', '"', '\\');
            }

            fclose($out);
        }, $archivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Excel ejecuta como fórmula el texto que empieza con =, +, -, @ (p. ej. un
     * nombre «=HIPERVINCULO(...)»); con un apóstrofo delante se muestra como texto.
     */
    public static function celda(mixed $valor): mixed
    {
        if (is_string($valor) && $valor !== '' && in_array($valor[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$valor;
        }

        return $valor;
    }
}
