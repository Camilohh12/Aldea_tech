<?php

namespace App\Providers;

use Illuminate\Http\Middleware\TrustProxies;
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
    }
}
