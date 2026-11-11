import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/admin.css',
                'resources/js/app.js',
                'resources/js/pases.js',
                'resources/js/pase-lista.js',
                // Panel admin y portada: no cargan app.js, solo este script
                'resources/js/filtros-limpios.js',
            ],
            refresh: true,
        }),
    ],
});
