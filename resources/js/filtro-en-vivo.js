/**
 * Búsqueda mientras escribes.
 *
 * En los formularios GET marcados con data-filtro-vivo, cada letra (tras una
 * pausa corta) pide la misma página con la búsqueda nueva y reemplaza solo las
 * partes marcadas con data-resultados="nombre": la tabla, la paginación, el
 * botón «Limpiar», las pestañas... El cuadro de búsqueda no se toca, así que el
 * cursor sigue donde estaba.
 *
 * El filtrado, la paginación y los permisos siguen en el servidor (es la misma
 * página de siempre), y la dirección se actualiza para poder recargar o
 * compartir. Los selects del formulario se aplican al cambiarlos. Si algo
 * falla, se abre la página normal con los filtros.
 */

const ESPERA_MS = 300; // pausa después de la última letra antes de buscar

const pausas = new WeakMap(); // formulario -> temporizador
const peticiones = new WeakMap(); // formulario -> AbortController de la búsqueda en curso
const ultimas = new WeakMap(); // formulario -> última dirección pedida

const esFiltroVivo = (form) => form instanceof HTMLFormElement && form.hasAttribute('data-filtro-vivo');

// Misma dirección que daría el formulario, sin campos vacíos (como filtros-limpios.js)
const direccion = (form) => {
    const url = new URL(form.getAttribute('action') || location.href, location.href);
    url.search = '';

    for (const [clave, valor] of new FormData(form)) {
        if (typeof valor === 'string' && valor.trim() !== '') {
            url.searchParams.append(clave, valor);
        }
    }

    return url;
};

const marcarCargando = (regiones, cargando) => regiones.forEach((region) => {
    region.toggleAttribute('aria-busy', cargando);
    region.style.opacity = cargando ? '0.6' : '';
    region.style.transition = 'opacity .15s';
});

async function buscar(form) {
    const url = direccion(form);

    if (ultimas.get(form) === url.href) {
        return; // nada cambió (p. ej. un espacio al final)
    }
    ultimas.set(form, url.href);

    peticiones.get(form)?.abort(); // solo cuenta la búsqueda más reciente
    const control = new AbortController();
    peticiones.set(form, control);

    const regiones = [...document.querySelectorAll('[data-resultados]')];
    marcarCargando(regiones, true);

    try {
        const respuesta = await fetch(url, {
            headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
            signal: control.signal,
        });

        // Sesión vencida u otro error: mejor la página completa
        if (!respuesta.ok || respuesta.redirected) {
            throw new Error(`Respuesta ${respuesta.status}`);
        }

        const pagina = new DOMParser().parseFromString(await respuesta.text(), 'text/html');
        const cambios = regiones.map((region) => [
            region,
            pagina.querySelector(`[data-resultados="${CSS.escape(region.dataset.resultados)}"]`),
        ]);

        if (cambios.some(([, nueva]) => !nueva)) {
            throw new Error('La respuesta no trae todas las partes');
        }

        cambios.forEach(([region, nueva]) => region.replaceWith(document.importNode(nueva, true)));
        history.replaceState(history.state, '', url);
    } catch (error) {
        if (error.name === 'AbortError') {
            return; // la reemplazó una búsqueda más nueva
        }
        marcarCargando(regiones, false);
        location.assign(url);
    }
}

const programar = (form, espera) => {
    clearTimeout(pausas.get(form));
    pausas.set(form, setTimeout(() => buscar(form), espera));
};

// Cada letra en el cuadro de búsqueda
document.addEventListener('input', (event) => {
    const campo = event.target;

    if (event.isComposing || !esFiltroVivo(campo.form) || !campo.matches('input[type="search"], input[type="text"]')) {
        return;
    }

    programar(campo.form, ESPERA_MS);
});

// Los selects (tipo, día, estado...) se aplican en cuanto cambian
document.addEventListener('change', (event) => {
    const campo = event.target;

    if (esFiltroVivo(campo.form) && campo.matches('select')) {
        programar(campo.form, 0);
    }
});

// Enter o el botón «Filtrar»: lo mismo, sin recargar la página
document.addEventListener('submit', (event) => {
    if (!esFiltroVivo(event.target)) {
        return;
    }

    event.preventDefault();
    programar(event.target, 0);
});
