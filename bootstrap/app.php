<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        
        // Registrar alias del middleware de Spatie
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // Solo se atiende el dominio de APP_URL (y sus subdominios): una visita que
        // diga venir de otro dominio recibe un error 400 en lugar de generar enlaces
        // hacia ese dominio. No aplica en local ni en las pruebas. El de Railway es el
        // dominio con el que revisa /up antes de publicar.
        $middleware->trustHosts(at: ['^healthcheck\.railway\.app$']);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();