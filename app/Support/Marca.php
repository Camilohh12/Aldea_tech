<?php

namespace App\Support;

/**
 * Color institucional para los PDF.
 *
 * Lee resources/css/marca.json, el mismo archivo que usa tailwind.config.js,
 * y arma la misma escala "aldea" (50 a 900) que tienen las pantallas.
 * Se consulta con config('aldea.marca'), por ejemplo config('aldea.marca.800').
 */
final class Marca
{
    /** @return array<int, string> tono => color hexadecimal */
    public static function escala(): array
    {
        $marca = json_decode((string) file_get_contents(resource_path('css/marca.json')), true);
        $base = strtolower($marca['institucional']);
        [$matiz, $saturacion] = self::aHsl($base);

        $escala = [];
        foreach ($marca['escala'] as $tono => $valores) {
            $escala[(int) $tono] = $valores === null
                ? $base
                : self::aHex($matiz, $saturacion * $valores[1], $valores[0]);
        }

        return $escala;
    }

    /** "#3c1860" => [matiz 0-360, saturación 0-1, luminosidad 0-1] */
    private static function aHsl(string $hex): array
    {
        [$r, $g, $b] = array_map(fn ($c) => $c / 255, sscanf($hex, '#%02x%02x%02x'));
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        $d = $max - $min;

        if ($d == 0) {
            return [0, 0, $l];
        }

        $s = $d / (1 - abs(2 * $l - 1));
        $h = match ($max) {
            $r => fmod(($g - $b) / $d + 6, 6),
            $g => ($b - $r) / $d + 2,
            default => ($r - $g) / $d + 4,
        };

        return [$h * 60, $s, $l];
    }

    private static function aHex(float $h, float $s, float $l): string
    {
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $l - $c / 2;

        [$r, $g, $b] = match (intdiv((int) $h, 60) % 6) {
            0 => [$c, $x, 0],
            1 => [$x, $c, 0],
            2 => [0, $c, $x],
            3 => [0, $x, $c],
            4 => [$x, 0, $c],
            default => [$c, 0, $x],
        };

        return sprintf('#%02x%02x%02x', ...array_map(fn ($v) => (int) round(($v + $m) * 255), [$r, $g, $b]));
    }
}
