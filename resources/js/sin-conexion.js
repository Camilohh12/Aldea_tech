/**
 * Registra el service worker (public/sw.js) para que «Mis pases», «Mi agenda» y el
 * pase de lista abran sin internet, y le pide guardar de antemano las páginas que
 * se usarán en el evento: el alumno, sus pases; el responsable, cada una de sus actividades.
 */
const ACTIVIDAD = /^\/pase-lista\/\d+$/;

const paginasPorGuardar = () => {
    const ruta = location.pathname;

    if (ruta.startsWith('/alumno/')) {
        return ['/alumno/pases', '/alumno/agenda'];
    }

    if (ruta === '/pase-lista') {
        const actividades = [...document.querySelectorAll('a[href]')]
            .map((enlace) => new URL(enlace.href, location.origin))
            .filter((url) => url.origin === location.origin && ACTIVIDAD.test(url.pathname))
            .map((url) => url.pathname);

        return ['/pase-lista', ...new Set(actividades)];
    }

    if (ACTIVIDAD.test(ruta)) {
        return [ruta, '/pase-lista'];
    }

    return [];
};

if ('serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', async () => {
        try {
            // Sin controlador es la primera visita: la página actual aún no quedó guardada
            const primeraVez = !navigator.serviceWorker.controller;
            const registro = await navigator.serviceWorker.register('/sw.js');

            const rutas = paginasPorGuardar().filter((ruta) => primeraVez || ruta !== location.pathname);
            if (rutas.length === 0 || !navigator.onLine) {
                return;
            }

            const activo = registro.active ?? (await navigator.serviceWorker.ready).active;
            activo?.postMessage({ tipo: 'guardar', rutas });
        } catch {
            // Sin service worker el sistema funciona igual; solo no abre sin internet
        }
    });
}
