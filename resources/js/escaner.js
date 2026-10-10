/**
 * Escáner QR del alumno + registro de asistencia sin conexión.
 *
 * Flujo:
 *  1. El alumno escanea el QR del aula (contiene el checkin_token de la actividad).
 *  2. Con Internet  -> POST /alumno/asistencias y se muestra el resultado.
 *     Sin Internet  -> se guarda en LocalStorage con la hora del escaneo.
 *  3. Al volver la conexión (evento "online") o al abrir la página, la cola se
 *     envía a /alumno/asistencias/sync y el servidor valida cada registro.
 *
 * Para mostrar el nombre de la actividad sin Internet, la página trae un mapa
 * sha256(token) -> {title, type, room, time} de las actividades del alumno.
 */
import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';

const app = document.getElementById('escaner-app');

if (app) {
    const STORE_URL = app.dataset.storeUrl;
    const SYNC_URL = app.dataset.syncUrl;
    // La cola es por usuario, por si dos alumnos usan el mismo teléfono.
    const QUEUE_KEY = `aldea.asistencias.pendientes.${app.dataset.userId}`;
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;

    let CATALOGO = {};
    try {
        CATALOGO = JSON.parse(document.getElementById('catalogo-offline')?.textContent || '{}') || {};
    } catch {
        CATALOGO = {};
    }

    const $ = (id) => document.getElementById(id);
    const btnCamara = $('btn-camara');
    const pendientes = $('pendientes');

    let scanner = null;
    let ocupado = false;
    let sincronizando = false;

    // ------------------------------------------------------------ Utilidades

    const hora = (fecha = new Date()) =>
        new Date(fecha).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', hour12: false });

    const sha256 = async (texto) => {
        if (!window.crypto?.subtle) return null;
        const buffer = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(texto));
        return [...new Uint8Array(buffer)].map((b) => b.toString(16).padStart(2, '0')).join('');
    };

    const actividadLocal = async (token) => CATALOGO[(await sha256(token)) ?? ''] ?? null;

    /** Crea un elemento con clases y texto (sin innerHTML para no inyectar nada). */
    const el = (tag, className = '', texto = null) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (texto !== null) node.textContent = texto;
        return node;
    };

    const ICONOS = {
        ok: 'M9 12l2 2 4-4M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        error: 'M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        cola: 'M7 18h10a4 4 0 00.5-7.97A6 6 0 006.1 9.1 4.5 4.5 0 007 18z',
        ubicacion: 'M12 21s-7-6.2-7-11.5A7 7 0 0119 9.5C19 14.8 12 21 12 21zM12 12a2.5 2.5 0 100-5 2.5 2.5 0 000 5z',
    };

    const icono = (nombre, className) => {
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 24 24');
        svg.setAttribute('fill', 'none');
        svg.setAttribute('stroke', 'currentColor');
        svg.setAttribute('stroke-width', '2');
        svg.setAttribute('class', className);
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('stroke-linecap', 'round');
        path.setAttribute('stroke-linejoin', 'round');
        path.setAttribute('d', ICONOS[nombre]);
        svg.append(path);
        return svg;
    };

    // ------------------------------------------------------------ Tarjeta de resultado

    const ESTILOS = {
        ok: { card: 'bg-white border border-emerald-100 shadow-sm', icon: 'bg-emerald-50 text-emerald-600', pill: 'bg-emerald-100 text-emerald-800', titulo: '¡Asistencia registrada!' },
        error: { card: 'bg-white border border-red-100 shadow-sm', icon: 'bg-red-50 text-red-600', pill: 'bg-red-100 text-red-700', titulo: 'No se pudo registrar' },
        cola: { card: 'bg-white border border-aldea-100 shadow-sm', icon: 'bg-aldea-50 text-aldea-700', pill: 'bg-aldea-100 text-aldea-800', titulo: 'Guardado sin conexión' },
    };

    /**
     * @param {'ok'|'error'|'cola'} tipo
     * @param {{mensaje?: string, actividad?: object|null, hora?: string}} datos
     */
    const mostrar = (tipo, { mensaje = '', actividad = null, hora: horaTexto = hora() } = {}) => {
        const estilo = ESTILOS[tipo];
        const card = $('resultado');
        card.className = `rounded-2xl p-4 ${estilo.card}`;

        const fila = el('div', 'flex items-start gap-3');
        const circulo = el('span', `grid h-11 w-11 shrink-0 place-items-center rounded-xl ${estilo.icon}`);
        circulo.append(icono(tipo, 'h-6 w-6'));

        const cuerpo = el('div', 'min-w-0 flex-1');
        const cabecera = el('div', 'flex items-start justify-between gap-2');
        cabecera.append(el('p', 'text-lg font-bold leading-tight', estilo.titulo));
        cabecera.append(el('span', `shrink-0 rounded-full px-2.5 py-0.5 text-xs font-bold ${estilo.pill}`, `${horaTexto} hrs`));
        cuerpo.append(cabecera);

        if (actividad?.title) {
            const prefijo = actividad.type ? `${actividad.type}: ` : '';
            cuerpo.append(el('p', 'mt-1 text-[15px] text-slate-700', `${prefijo}«${actividad.title}»`));
        }

        if (mensaje && (tipo !== 'ok' || !actividad)) {
            cuerpo.append(el('p', 'mt-1 text-sm text-slate-600', mensaje));
        }

        if (actividad?.room) {
            const sede = el('p', 'mt-2 flex items-center gap-1.5 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600');
            sede.append(icono('ubicacion', 'h-4 w-4 text-aldea-700'), document.createTextNode(actividad.room));
            cuerpo.append(sede);
        }

        fila.append(circulo, cuerpo);
        card.replaceChildren(fila);
        $('resultado-wrap').hidden = false;

        if (tipo === 'ok' && navigator.vibrate) navigator.vibrate(120);
    };

    // ------------------------------------------------------------ Cola (LocalStorage)

    const leerCola = () => {
        try {
            return JSON.parse(localStorage.getItem(QUEUE_KEY)) ?? [];
        } catch {
            return [];
        }
    };

    const guardarCola = (cola) => {
        try {
            localStorage.setItem(QUEUE_KEY, JSON.stringify(cola));
            return true;
        } catch {
            return false; // Modo incógnito o almacenamiento lleno.
        }
    };

    const encolar = async (token, aviso = null) => {
        const cola = leerCola();
        const actividad = await actividadLocal(token);

        if (cola.some((item) => item.token === token)) {
            mostrar('cola', { actividad, mensaje: 'Este código ya está guardado y se enviará al recuperar la conexión.' });
            return;
        }

        cola.push({
            id: crypto.randomUUID ? crypto.randomUUID() : `${Date.now()}-${Math.random()}`,
            token,
            scanned_at: new Date().toISOString(),
            actividad, // Solo para mostrarlo en pantalla; el servidor valida con el token.
        });

        if (guardarCola(cola)) {
            mostrar('cola', {
                actividad,
                mensaje: aviso ?? 'No hay Internet. Tu asistencia quedó guardada en el teléfono y se enviará automáticamente.',
            });
        } else {
            mostrar('error', { mensaje: 'Este navegador no permite guardar sin conexión. Avisa al ponente.' });
        }

        pintarCola();
    };

    const pintarCola = () => {
        const cola = leerCola();
        pendientes.hidden = cola.length === 0;
        pendientes.querySelector('[data-contador]').textContent = `${cola.length} ${cola.length === 1 ? 'escaneo' : 'escaneos'}`;

        pendientes.querySelector('[data-lista]').replaceChildren(
            ...cola.map((item) => {
                const li = el('li', 'flex items-center gap-3 rounded-xl bg-white px-3 py-2.5');
                li.append(el('span', 'h-2 w-2 shrink-0 rounded-full bg-aldea-500'));

                const texto = el('div', 'min-w-0 flex-1 leading-tight');
                texto.append(el('p', 'truncate text-sm font-semibold', item.actividad?.title ?? `Código ${item.token.slice(0, 6)}…`));
                const detalle = [`${hora(item.scanned_at)} hrs`, item.actividad?.room].filter(Boolean).join(' • ');
                texto.append(el('p', 'truncate text-xs text-slate-500', detalle));

                li.append(texto, el('span', 'shrink-0 rounded-md bg-aldea-50 px-2 py-1 text-xs font-semibold text-aldea-700', 'En cola'));
                return li;
            }),
        );
    };

    // ------------------------------------------------------------ Red

    const post = (url, body) =>
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify(body),
        });

    const registrar = async (token) => {
        if (!navigator.onLine) {
            await encolar(token);
            return;
        }

        try {
            const res = await post(STORE_URL, { token });

            if (res.status === 401 || res.status === 419) {
                await encolar(token, 'Tu sesión expiró. Guardamos el registro; vuelve a iniciar sesión para enviarlo.');
                return;
            }

            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                mostrar('ok', { actividad: data.activity, hora: data.checkin_time ?? hora(), mensaje: data.message });
            } else {
                mostrar('error', {
                    actividad: await actividadLocal(token),
                    mensaje: data.message ?? 'No se pudo registrar la asistencia.',
                });
            }
        } catch {
            // fetch falla sin respuesta = la red se cayó aunque navigator.onLine diga lo contrario.
            await encolar(token);
        }
    };

    const sincronizar = async () => {
        const cola = leerCola();
        if (sincronizando || cola.length === 0 || !navigator.onLine) return;

        sincronizando = true;
        $('btn-sincronizar').disabled = true;

        try {
            const items = cola.slice(0, 50).map(({ id, token, scanned_at }) => ({ id, token, scanned_at }));
            const res = await post(SYNC_URL, { items });
            if (!res.ok) return; // Sesión expirada o error del servidor: se reintenta después.

            const { results } = await res.json();
            const procesados = new Set(results.map((r) => r.id));

            // Volvemos a leer por si se escaneó algo mientras se enviaba.
            guardarCola(leerCola().filter((item) => !procesados.has(item.id)));
            pintarCola();

            const exitos = results.filter((r) => r.ok);
            const errores = results.filter((r) => !r.ok);

            if (errores.length) {
                const resumen = exitos.length ? `${exitos.length} enviada(s) correctamente. ` : '';
                mostrar('error', { mensaje: resumen + errores.map((e) => e.message).join(' ') });
            } else if (exitos.length === 1) {
                mostrar('ok', { actividad: exitos[0].activity, hora: exitos[0].checkin_time, mensaje: exitos[0].message });
            } else if (exitos.length > 1) {
                mostrar('ok', { mensaje: `Conexión recuperada: se enviaron ${exitos.length} asistencias guardadas.` });
            }
        } catch {
            // Sigue sin red; se reintentará con el próximo evento "online".
        } finally {
            sincronizando = false;
            $('btn-sincronizar').disabled = false;
        }
    };

    const pintarRed = () => {
        const enLinea = navigator.onLine;
        $('red-punto').className = `h-2.5 w-2.5 shrink-0 rounded-full ${enLinea ? 'bg-emerald-500' : 'bg-amber-500'}`;
        $('red-titulo').textContent = enLinea ? 'En línea' : 'Sin conexión';
        $('red-detalle').textContent = enLinea
            ? 'Servidor UPP disponible'
            : 'Los escaneos se guardan en este teléfono';
        const pill = $('red-pill');
        pill.className = `inline-flex shrink-0 items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-semibold ${
            enLinea ? 'border-aldea-100 bg-aldea-50 text-aldea-800' : 'border-amber-200 bg-amber-50 text-amber-800'
        }`;
        pill.querySelector('[data-texto]').textContent = enLinea ? 'Conectado' : 'Modo offline';
    };

    // ------------------------------------------------------------ Cámara

    const pintarCamara = (activa) => {
        $('camara-estado').replaceChildren(
            el('span', `h-2 w-2 rounded-full ${activa ? 'bg-aldea-300 animate-pulse' : 'bg-slate-400'}`),
            document.createTextNode(activa ? ' Cámara activa' : ' Cámara apagada'),
        );
        $('camara-icono').hidden = activa;
        $('camara-linea').hidden = !activa;
        btnCamara.querySelector('[data-texto]').textContent = activa ? 'Pausar cámara' : 'Activar cámara';
    };

    /** El QR puede traer solo el token o una URL con ?token=... */
    const extraerToken = (texto) => {
        const limpio = texto.trim();
        try {
            return new URL(limpio).searchParams.get('token') ?? limpio;
        } catch {
            return limpio;
        }
    };

    const alEscanear = async (texto) => {
        if (ocupado) return;
        ocupado = true;
        try {
            scanner?.pause(true);
        } catch {
            /* no estaba escaneando */
        }

        const token = extraerToken(texto);
        if (token.length === 0 || token.length > 64) {
            mostrar('error', { mensaje: 'Este QR no es un código de asistencia de Aldea Tecnológica.' });
        } else {
            await registrar(token);
        }

        // Pausa breve para que el mismo QR no se lea varias veces seguidas.
        setTimeout(() => {
            ocupado = false;
            try {
                scanner?.resume();
            } catch {
                /* el escáner ya se detuvo */
            }
        }, 2500);
    };

    const iniciarCamara = async () => {
        if (!window.isSecureContext) {
            mostrar('error', { mensaje: 'La cámara solo funciona con HTTPS (o en localhost). Usa el registro manual.' });
            return;
        }

        scanner ??= new Html5Qrcode('lector', {
            formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
            verbose: false,
        });

        try {
            await scanner.start({ facingMode: 'environment' }, { fps: 10 }, alEscanear, () => {});
            pintarCamara(true);
        } catch (err) {
            mostrar('error', { mensaje: `No se pudo abrir la cámara. Revisa que diste permiso al navegador. ${err?.message ?? ''}` });
        }
    };

    const detenerCamara = async () => {
        try {
            if (scanner?.isScanning) await scanner.stop();
        } finally {
            pintarCamara(false);
        }
    };

    // ------------------------------------------------------------ Eventos

    btnCamara.addEventListener('click', () => (scanner?.isScanning ? detenerCamara() : iniciarCamara()));
    $('btn-sincronizar').addEventListener('click', sincronizar);

    $('form-manual').addEventListener('submit', (e) => {
        e.preventDefault();
        const input = e.target.elements.codigo;
        alEscanear(input.value);
        input.value = '';
    });

    window.addEventListener('online', () => {
        pintarRed();
        sincronizar();
    });
    window.addEventListener('offline', pintarRed);
    window.addEventListener('pagehide', detenerCamara);

    pintarRed();
    pintarCamara(false);
    pintarCola();
    sincronizar();
}
