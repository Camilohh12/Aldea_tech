/**
 * Pase de lista del tutor: escanea el QR del pase de cada alumno.
 *
 * Flujo:
 *  1. El tutor abre la actividad. La página trae la lista de inscritos con la
 *     huella sha256 del código de cada pase (nunca el código real).
 *  2. Al escanear, se calcula la huella para reconocer al alumno al instante.
 *  3. Con Internet  -> POST al servidor y se marca en la lista.
 *     Sin Internet  -> se guarda en LocalStorage con la hora y se marca "pendiente".
 *  4. Al volver la conexión la cola se envía a /sync; el servidor valida que la
 *     hora del escaneo esté dentro del horario de la actividad.
 */
import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';

const app = document.getElementById('pase-lista-app');

if (app) {
    const STORE_URL = app.dataset.storeUrl;
    const SYNC_URL = app.dataset.syncUrl;
    const QUEUE_KEY = `aldea.paselista.${app.dataset.staffId}.${app.dataset.activityId}`;
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;

    const $ = (id) => document.getElementById(id);

    /** hash -> {id, name, matricula, attended, time} */
    const ROSTER = new Map();
    try {
        JSON.parse($('roster').textContent || '[]').forEach((a) => ROSTER.set(a.hash, a));
    } catch {
        /* sin lista: solo funcionará en línea */
    }
    const porId = (id) => [...ROSTER.values()].find((a) => a.id === id);

    let scanner = null;
    let ocupado = false;
    let sincronizando = false;
    let ultimoToken = null;
    let ultimoMomento = 0;

    // ------------------------------------------------------------ Utilidades

    const hora = (fecha = new Date()) =>
        new Date(fecha).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', hour12: false });

    const sha256 = async (texto) => {
        if (!window.crypto?.subtle) return null;
        const buffer = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(texto));
        return [...new Uint8Array(buffer)].map((b) => b.toString(16).padStart(2, '0')).join('');
    };

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
        info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
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

    // ------------------------------------------------------------ Resultado

    const ESTILOS = {
        ok: { card: 'bg-white border border-emerald-200 shadow-sm', icon: 'bg-emerald-50 text-emerald-600', titulo: 'Asistencia registrada' },
        info: { card: 'bg-white border border-sky-200 shadow-sm', icon: 'bg-sky-50 text-sky-600', titulo: 'Ya estaba registrado' },
        error: { card: 'bg-white border border-red-200 shadow-sm', icon: 'bg-red-50 text-red-600', titulo: 'Pase no válido' },
        cola: { card: 'bg-white border border-aldea-200 shadow-sm', icon: 'bg-aldea-50 text-aldea-700', titulo: 'Guardado sin conexión' },
    };

    const SONIDOS = { ok: [880, 0.12], info: [660, 0.12], cola: [660, 0.12], error: [220, 0.3] };

    const pitido = (tipo) => {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const [freq, dur] = SONIDOS[tipo];
            osc.frequency.value = freq;
            osc.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + dur);
        } catch {
            /* sin audio */
        }
        if (navigator.vibrate) navigator.vibrate(tipo === 'error' ? [80, 60, 80] : 120);
    };

    const mostrar = (tipo, { alumno = null, mensaje = '', horaTexto = hora() } = {}) => {
        const estilo = ESTILOS[tipo];
        const card = $('resultado');
        card.className = `rounded-2xl p-4 ${estilo.card}`;

        const fila = el('div', 'flex items-start gap-3');
        const circulo = el('span', `grid h-12 w-12 shrink-0 place-items-center rounded-xl ${estilo.icon}`);
        circulo.append(icono(tipo, 'h-7 w-7'));

        const cuerpo = el('div', 'min-w-0 flex-1');
        const cabecera = el('div', 'flex items-start justify-between gap-2');
        cabecera.append(el('p', 'font-bold leading-tight', estilo.titulo));
        cabecera.append(el('span', 'shrink-0 text-xs font-semibold text-slate-500', `${horaTexto} hrs`));
        cuerpo.append(cabecera);

        if (alumno) {
            cuerpo.append(el('p', 'mt-1 text-lg font-semibold leading-snug', alumno.name));
            if (alumno.matricula) cuerpo.append(el('p', 'text-sm text-slate-500', `Matrícula ${alumno.matricula}`));
        }
        if (mensaje) cuerpo.append(el('p', 'mt-1 text-sm text-slate-600', mensaje));

        fila.append(circulo, cuerpo);
        card.replaceChildren(fila);
        $('resultado-wrap').hidden = false;
        pitido(tipo);
    };

    // ------------------------------------------------------------ Lista de alumnos

    const pintarFila = (enrollmentId, estado, horaTexto = null) => {
        const fila = document.querySelector(`[data-enrollment="${enrollmentId}"] [data-estado]`);
        if (!fila) return;
        const clases = {
            ok: 'rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700',
            pendiente: 'rounded-full bg-aldea-100 px-2.5 py-1 text-xs font-semibold text-aldea-800',
        };
        fila.replaceChildren(el('span', clases[estado], estado === 'ok' ? `✓ ${horaTexto ?? ''}`.trim() : '⏳ Por enviar'));
    };

    const actualizarContador = () => {
        const presentes = [...ROSTER.values()].filter((a) => a.attended).length;
        document.querySelector('[data-presentes]').textContent = presentes;
    };

    const marcarPresente = (enrollmentId, horaTexto) => {
        const alumno = porId(enrollmentId);
        if (alumno) {
            alumno.attended = true;
            alumno.time = horaTexto;
        }
        pintarFila(enrollmentId, 'ok', horaTexto);
        actualizarContador();
    };

    $('buscar-alumno')?.addEventListener('input', (e) => {
        const q = e.target.value.trim().toLowerCase();
        document.querySelectorAll('#lista-alumnos [data-buscar]').forEach((li) => {
            li.hidden = q !== '' && !li.dataset.buscar.includes(q);
        });
    });

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
            return false;
        }
    };

    const pintarCola = () => {
        const cola = leerCola();
        $('pendientes').hidden = cola.length === 0;
        $('pendientes').querySelector('[data-contador]').textContent = `${cola.length} ${cola.length === 1 ? 'escaneo' : 'escaneos'}`;
        cola.forEach((item) => item.enrollment_id && pintarFila(item.enrollment_id, 'pendiente'));
    };

    const encolar = (token, alumno) => {
        const cola = leerCola();

        if (cola.some((item) => item.token === token)) {
            mostrar('cola', { alumno, mensaje: 'Este pase ya está guardado y se enviará al recuperar la conexión.' });
            return;
        }

        cola.push({
            id: crypto.randomUUID ? crypto.randomUUID() : `${Date.now()}-${Math.random()}`,
            token,
            scanned_at: new Date().toISOString(),
            enrollment_id: alumno?.id ?? null,
        });

        if (guardarCola(cola)) {
            mostrar('cola', { alumno, mensaje: 'Sin Internet: se enviará automáticamente al recuperar la conexión.' });
        } else {
            mostrar('error', { alumno, mensaje: 'Este navegador no permite guardar sin conexión. Usa el registro manual cuando vuelva el Internet.' });
        }
        pintarCola();
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
        const alumno = ROSTER.get((await sha256(token)) ?? '') ?? null;

        if (!navigator.onLine) {
            if (!alumno) {
                mostrar('error', { mensaje: 'Este pase no pertenece a ningún alumno inscrito en esta actividad.' });
            } else if (alumno.attended) {
                mostrar('info', { alumno, mensaje: `Asistencia registrada a las ${alumno.time} hrs.`, horaTexto: alumno.time });
            } else {
                encolar(token, alumno);
            }
            return;
        }

        try {
            const res = await post(STORE_URL, { token });

            if (res.status === 401 || res.status === 419) {
                if (alumno) encolar(token, alumno);
                mostrar('error', { alumno, mensaje: 'Tu sesión expiró. Vuelve a iniciar sesión; el escaneo quedó guardado.' });
                return;
            }

            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                marcarPresente(data.enrollment_id, data.checkin_time);
                mostrar(data.already ? 'info' : 'ok', {
                    alumno: data.student,
                    mensaje: data.already ? `Su asistencia ya estaba registrada (${data.checkin_time} hrs).` : '',
                    horaTexto: data.checkin_time ?? hora(),
                });
            } else {
                mostrar('error', { alumno, mensaje: data.message ?? 'No se pudo registrar la asistencia.' });
            }
        } catch {
            // Sin respuesta: la red se cayó aunque el navegador diga que hay conexión.
            if (alumno) {
                encolar(token, alumno);
            } else {
                mostrar('error', { mensaje: 'Sin conexión y el pase no está en la lista de esta actividad.' });
            }
        }
    };

    const sincronizar = async () => {
        const cola = leerCola();
        if (sincronizando || cola.length === 0 || !navigator.onLine) return;

        sincronizando = true;
        $('btn-sincronizar').disabled = true;

        try {
            const items = cola.slice(0, 200).map(({ id, token, scanned_at }) => ({ id, token, scanned_at }));
            const res = await post(SYNC_URL, { items });
            if (!res.ok) return;

            const { results } = await res.json();
            const procesados = new Set(results.map((r) => r.id));
            guardarCola(leerCola().filter((item) => !procesados.has(item.id)));

            results.filter((r) => r.ok).forEach((r) => marcarPresente(r.enrollment_id, r.checkin_time));
            pintarCola();

            const exitos = results.filter((r) => r.ok).length;
            const errores = results.filter((r) => !r.ok);

            if (errores.length) {
                const resumen = exitos ? `${exitos} enviada(s). ` : '';
                mostrar('error', { mensaje: resumen + errores.map((e) => e.message).join(' ') });
            } else if (exitos) {
                mostrar('ok', { mensaje: `Conexión recuperada: se enviaron ${exitos} asistencia(s) guardadas.` });
            }
        } catch {
            /* se reintenta con el próximo evento "online" */
        } finally {
            sincronizando = false;
            $('btn-sincronizar').disabled = false;
        }
    };

    const pintarRed = () => {
        const enLinea = navigator.onLine;
        $('red-punto').className = `h-2.5 w-2.5 shrink-0 rounded-full ${enLinea ? 'bg-emerald-500' : 'bg-amber-500'}`;
        $('red-titulo').textContent = enLinea ? 'En línea' : 'Sin conexión';
        $('red-detalle').textContent = enLinea ? 'Registro al momento' : 'Se guarda en el dispositivo';
    };

    // ------------------------------------------------------------ Cámara

    const btnCamara = $('btn-camara');

    const pintarCamara = (activa) => {
        $('camara-estado').replaceChildren(
            el('span', `h-2 w-2 rounded-full ${activa ? 'bg-aldea-300 animate-pulse' : 'bg-slate-400'}`),
            document.createTextNode(activa ? ' Cámara activa' : ' Cámara apagada'),
        );
        $('camara-icono').hidden = activa;
        $('camara-linea').hidden = !activa;
        btnCamara.querySelector('[data-texto]').textContent = activa ? 'Pausar cámara' : 'Activar cámara';
    };

    const alEscanear = async (texto) => {
        const token = texto.trim();
        const ahora = Date.now();

        // El mismo QR se lee muchas veces por segundo: lo ignoramos unos segundos.
        if (ocupado || (token === ultimoToken && ahora - ultimoMomento < 4000)) return;
        ocupado = true;
        ultimoToken = token;
        ultimoMomento = ahora;

        if (token.length === 0 || token.length > 64) {
            mostrar('error', { mensaje: 'Este QR no es un pase de Aldea Tecnológica.' });
        } else {
            await registrar(token);
        }

        // Pausa corta para que el tutor vea el resultado antes del siguiente alumno.
        setTimeout(() => {
            ocupado = false;
        }, 1200);
    };

    const iniciarCamara = async () => {
        if (!window.isSecureContext) {
            mostrar('error', { mensaje: 'La cámara solo funciona con HTTPS (o en localhost).' });
            return;
        }

        scanner ??= new Html5Qrcode('lector', {
            formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
            verbose: false,
        });

        try {
            await scanner.start({ facingMode: 'environment' }, { fps: 12 }, alEscanear, () => {});
            pintarCamara(true);
        } catch (err) {
            mostrar('error', { mensaje: `No se pudo abrir la cámara. Revisa los permisos del navegador. ${err?.message ?? ''}` });
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
    window.addEventListener('online', () => {
        pintarRed();
        sincronizar();
    });
    window.addEventListener('offline', pintarRed);
    window.addEventListener('pagehide', detenerCamara);

    // Para pruebas sin cámara: window.paseLista.escanear('codigo')
    window.paseLista = { escanear: alEscanear };

    pintarRed();
    pintarCamara(false);
    pintarCola();
    sincronizar();
}
