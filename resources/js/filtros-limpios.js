import './filtro-en-vivo';

/**
 * Direcciones limpias en los filtros y búsquedas.
 * (La búsqueda mientras escribes está en filtro-en-vivo.js.)
 *
 * Al enviar un formulario GET, los campos vacíos no se mandan:
 * la dirección queda como «?tipo=3» en lugar de «?q=&tipo=3&dia=».
 * Los campos se vuelven a habilitar justo después, por si el navegador
 * regresa a esta página con el botón Atrás.
 */
document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() !== 'get') {
        return;
    }

    const vacios = [...form.querySelectorAll('input[name], select[name], textarea[name]')]
        .filter((campo) => !campo.disabled && campo.type !== 'checkbox' && campo.type !== 'radio' && campo.value.trim() === '');

    vacios.forEach((campo) => { campo.disabled = true; });
    setTimeout(() => vacios.forEach((campo) => { campo.disabled = false; }), 0);
});
