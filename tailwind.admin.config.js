import base from './tailwind.config.js';

/**
 * Portada y panel de administración.
 * Mismos colores y fuentes que tailwind.config.js, pero sin @tailwindcss/forms:
 * estas pantallas se diseñaron con los controles nativos (como los dejaba el CDN).
 *
 * @type {import('tailwindcss').Config}
 */
export default {
    ...base,
    plugins: [],
};
