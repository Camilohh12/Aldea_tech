<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Los ids en las URL solo pueden ser números: /admin/rooms/abc/edit responde 404
        Route::patterns(array_fill_keys([
            'activity', 'activity_type', 'administrator', 'alumno', 'enrollment',
            'responsible', 'room', 'speaker', 'tutor',
        ], '[0-9]+'));

        // El enlace para restablecer la contraseña ya no lleva el correo en la URL
        // (queda en el historial del navegador); la persona lo escribe en el formulario.
        ResetPassword::createUrlUsing(
            fn ($user, string $token) => url(route('password.reset', ['token' => $token], false))
        );
    }
}
