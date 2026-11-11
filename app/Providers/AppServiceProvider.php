<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
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
        // Si la app se publica con HTTPS, todos los enlaces y recursos se generan con https://
        // (la cámara del pase de lista solo funciona en una conexión segura)
        URL::forceHttps(str_starts_with((string) config('app.url'), 'https://'));

        // Proxies de confianza (ver config/app.php) para reconocer HTTPS y la IP real del visitante
        if ($proxies = config('app.trusted_proxies')) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        // Los ids de las rutas solo pueden ser números: /admin/rooms/abc/edit responde 404
        // sin llegar a la base de datos. La autorización de cada recurso sigue en su controlador.
        Route::patterns(array_fill_keys([
            'activity', 'activity_type', 'administrator', 'alumno', 'enrollment',
            'responsible', 'room', 'speaker', 'tutor',
        ], '[0-9]+'));

        // El enlace para restablecer la contraseña no lleva el correo en la URL
        // (quedaría en el historial del navegador); el alumno lo escribe en el formulario.
        // Se arma con APP_URL y no con el dominio que manda quien pide el correo:
        // así nadie puede hacer que el enlace apunte a su propio sitio.
        ResetPassword::createUrlUsing(
            fn ($user, string $token) => rtrim((string) config('app.url'), '/')
                .route('password.reset', ['token' => $token], false)
        );

        // Registro: hasta 10 intentos por minuto con la misma matrícula y 60 por red.
        // El límite por red es alto a propósito: en la escuela muchos alumnos salen
        // a internet con la misma IP y se pueden registrar al mismo tiempo.
        RateLimiter::for('registro', function (Request $request) {
            $matricula = is_string($request->input('matricula')) ? $request->input('matricula') : '';
            $avisar = fn () => back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['matricula' => 'Demasiados intentos de registro. Espera un minuto y vuelve a intentarlo.']);

            return [
                Limit::perMinute(10)->by('registro:'.$request->ip().'|'.$matricula)->response($avisar),
                Limit::perMinute(60)->by('registro:'.$request->ip())->response($avisar),
            ];
        });
    }
}
