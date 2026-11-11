<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * El color institucional vive en resources/css/marca.json y de ahí sale todo:
 * Tailwind (pantallas) y config('aldea.marca') (PDF).
 */
class ColorInstitucionalTest extends TestCase
{
    public function test_la_escala_sale_del_color_del_escudo(): void
    {
        $marca = json_decode(File::get(resource_path('css/marca.json')), true);
        $escala = config('aldea.marca');

        $this->assertSame('#3C1860', $marca['institucional']);
        $this->assertSame([50, 100, 200, 300, 400, 500, 600, 700, 800, 900], array_keys($escala));
        $this->assertSame(strtolower($marca['institucional']), $escala[800]);

        // Cada tono es un color válido y la escala va de claro a oscuro
        $luz = fn (string $hex) => array_sum(sscanf($hex, '#%02x%02x%02x'));
        $anterior = PHP_INT_MAX;
        foreach ($escala as $tono => $hex) {
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $hex, "Tono {$tono}");
            $this->assertLessThan($anterior, $luz($hex), "El tono {$tono} debe ser más oscuro que el anterior");
            $anterior = $luz($hex);
        }
    }

    public function test_tailwind_lee_el_mismo_archivo(): void
    {
        $config = File::get(base_path('tailwind.config.js'));

        $this->assertStringContainsString("from './resources/css/marca.json'", $config);
        $this->assertStringNotContainsString('#340158', $config);
        $this->assertStringNotContainsString('#4B1F6F', $config);
    }

    public function test_las_vistas_no_usan_morados_viejos_ni_el_indigo_de_breeze(): void
    {
        $viejos = ['#340158', '#4b1f6f', '#26093f', '#4a1f80', '#3b1270', '#5a2d93', '#b49bd6', '#d7c8ea'];

        foreach (File::allFiles(resource_path('views')) as $archivo) {
            $contenido = strtolower($archivo->getContents());
            foreach ($viejos as $hex) {
                $this->assertStringNotContainsString($hex, $contenido, "{$archivo->getRelativePathname()} usa {$hex}; usa config('aldea.marca') o las clases upp/aldea.");
            }
            $this->assertStringNotContainsString('indigo-', $contenido, "{$archivo->getRelativePathname()} usa índigo; usa la escala aldea.");
            // upp-primary y upp-primary-container son el mismo color: el hover debe ir al tono oscuro
            $this->assertDoesNotMatchRegularExpression('/hover:(bg|text)-upp-primary(?![-\w])/', $contenido, $archivo->getRelativePathname());
        }
    }
}
