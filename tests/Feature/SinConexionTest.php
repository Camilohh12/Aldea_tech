<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * public/sw.js guarda «Mis pases», «Mi agenda» y el pase de lista para abrirlos sin internet.
 * Como es JavaScript plano, tiene escritas las rutas: si alguna cambia en routes/web.php,
 * estas pruebas avisan que hay que actualizar sw.js, offline.html y resources/js/sin-conexion.js.
 */
class SinConexionTest extends TestCase
{
    public function test_las_rutas_que_guarda_el_service_worker_no_cambiaron(): void
    {
        $this->assertSame('/alumno/pases', route('alumno.pases', [], false));
        $this->assertSame('/alumno/agenda', route('alumno.agenda', [], false));
        $this->assertSame('/pase-lista', route('pase-lista.index', [], false));
        $this->assertSame('/pase-lista/8', route('pase-lista.show', 8, false));

        // Al entrar o salir se borran las páginas guardadas (pueden ser de otra persona)
        $this->assertSame('/login', route('login', [], false));
        $this->assertSame('/logout', route('logout', [], false));
    }

    public function test_el_service_worker_y_la_pagina_sin_conexion_estan_publicados(): void
    {
        $sw = file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString('/^\/alumno\/(pases|agenda)$/', $sw);
        $this->assertStringContainsString('/^\/pase-lista(\/\d+)?$/', $sw);
        $this->assertStringContainsString("['/login', '/logout']", $sw);
        $this->assertFileExists(public_path('offline.html'));

        $sinConexion = file_get_contents(public_path('offline.html'));

        $this->assertStringContainsString('href="'.route('alumno.pases', [], false).'"', $sinConexion);
        $this->assertStringContainsString('href="'.route('pase-lista.index', [], false).'"', $sinConexion);
    }
}
