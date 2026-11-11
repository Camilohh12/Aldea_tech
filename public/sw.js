/**
 * Service worker de Aldea Tecnológica: permite abrir sin internet
 * «Mis pases» y «Mi agenda» (alumnos) y el pase de lista (responsables).
 *
 *  - Esas páginas se piden primero a la red; sin conexión se muestra la última copia guardada.
 *  - Los estilos y scripts de /build se guardan la primera vez (su nombre cambia en cada versión).
 *  - Al iniciar o cerrar sesión se borran las páginas guardadas: pueden ser de otra persona.
 *  - Cualquier otra página sin conexión muestra /offline.html.
 *
 * Si cambias este archivo de forma importante, sube VERSION para limpiar lo guardado.
 */
const VERSION = 'v1';
const PAGINAS = `aldea-paginas-${VERSION}`;
const RECURSOS = `aldea-recursos-${VERSION}`;
const SIN_CONEXION = '/offline.html';

// Páginas que se guardan para usarse sin internet (deben coincidir con routes/web.php)
const GUARDABLES = [/^\/alumno\/(pases|agenda)$/, /^\/pase-lista(\/\d+)?$/];

const esGuardable = (url) =>
    url.origin === self.location.origin && GUARDABLES.some((regla) => regla.test(url.pathname));

self.addEventListener('install', (event) => {
    event.waitUntil((async () => {
        const cache = await caches.open(RECURSOS);
        await cache.addAll([SIN_CONEXION, '/img/escudo-upp.png']);

        // Estilos y scripts de la versión publicada, para que las páginas guardadas se vean bien
        try {
            const manifiesto = await (await fetch('/build/manifest.json', { cache: 'no-store' })).json();
            const archivos = new Set();
            Object.values(manifiesto).forEach((entrada) => {
                archivos.add(`/build/${entrada.file}`);
                (entrada.css ?? []).forEach((css) => archivos.add(`/build/${css}`));
            });
            await cache.addAll([...archivos]);
        } catch {
            // Sin manifiesto (modo desarrollo con Vite): se guardan conforme se usan
        }

        await self.skipWaiting();
    })());
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const nombres = await caches.keys();
        await Promise.all(nombres
            .filter((nombre) => nombre.startsWith('aldea-') && ![PAGINAS, RECURSOS].includes(nombre))
            .map((nombre) => caches.delete(nombre)));
        await self.clients.claim();
    })());
});

/** Red primero; si no hay conexión, la copia guardada. */
const primeroRed = async (request, clave, nombreCache) => {
    const cache = await caches.open(nombreCache);

    try {
        const respuesta = await fetch(request);
        // Solo se guarda la página real, no una redirección al login (sesión vencida)
        if (respuesta.ok && !respuesta.redirected) {
            await cache.put(clave, respuesta.clone());
        }
        return respuesta;
    } catch {
        const guardado = await cache.match(clave, { ignoreVary: true });
        if (guardado) {
            return guardado;
        }
        return request.mode === 'navigate'
            ? (await caches.match(SIN_CONEXION)) ?? Response.error()
            : Response.error();
    }
};

/** Copia guardada primero (archivos con nombre único por versión). */
const primeroGuardado = async (request) => {
    const guardado = await caches.match(request, { ignoreVary: true });
    if (guardado) {
        return guardado;
    }

    const respuesta = await fetch(request);
    if (respuesta.ok) {
        const cache = await caches.open(RECURSOS);
        await cache.put(request, respuesta.clone());
    }
    return respuesta;
};

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    if (request.method === 'POST' && ['/login', '/logout'].includes(url.pathname)) {
        event.waitUntil(caches.delete(PAGINAS));
        return;
    }

    if (request.method !== 'GET') {
        return;
    }

    if (url.pathname.startsWith('/build/assets/')) {
        event.respondWith(primeroGuardado(request));
    } else if (url.pathname.startsWith('/img/')) {
        event.respondWith(primeroRed(request, request, RECURSOS));
    } else if (esGuardable(url)) {
        event.respondWith(primeroRed(request, url.pathname, PAGINAS));
    } else if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(async () => (await caches.match(SIN_CONEXION)) ?? Response.error()));
    }
});

// La página pide guardar de antemano otras páginas (p. ej. cada actividad del pase de lista)
self.addEventListener('message', (event) => {
    if (event.data?.tipo !== 'guardar') {
        return;
    }

    event.waitUntil((async () => {
        for (const ruta of event.data.rutas ?? []) {
            const url = new URL(ruta, self.location.origin);
            if (esGuardable(url)) {
                await primeroRed(new Request(url, { credentials: 'same-origin' }), url.pathname, PAGINAS);
            }
        }
    })());
});
