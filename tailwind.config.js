import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import marca from './resources/css/marca.json';

// ---------------------------------------------------------------------------
// Color institucional: se define una sola vez en resources/css/marca.json
// (tomado del escudo oficial). Aquí se arma su escala de tonos con el mismo
// cálculo que app/Support/Marca.php usa para los PDF.
// ---------------------------------------------------------------------------
const aHsl = (hex) => {
    const [r, g, b] = [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16) / 255);
    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    const l = (max + min) / 2;
    const d = max - min;
    if (d === 0) return [0, 0, l];
    const s = d / (1 - Math.abs(2 * l - 1));
    const h = max === r ? ((g - b) / d + 6) % 6 : max === g ? (b - r) / d + 2 : (r - g) / d + 4;
    return [h * 60, s, l];
};

const aHex = (h, s, l) => {
    const c = (1 - Math.abs(2 * l - 1)) * s;
    const x = c * (1 - Math.abs(((h / 60) % 2) - 1));
    const m = l - c / 2;
    const [r, g, b] = [[c, x, 0], [x, c, 0], [0, c, x], [0, x, c], [x, 0, c], [c, 0, x]][Math.floor(h / 60) % 6];
    return '#' + [r, g, b].map((v) => Math.round((v + m) * 255).toString(16).padStart(2, '0')).join('');
};

const institucional = marca.institucional.toLowerCase();
const [matiz, saturacion] = aHsl(institucional);

// aldea-50 … aldea-900; aldea-800 es el color institucional exacto
const aldea = Object.fromEntries(
    Object.entries(marca.escala).map(([tono, valores]) => [
        tono,
        valores === null ? institucional : aHex(matiz, saturacion * valores[1], valores[0]),
    ]),
);

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js', // clases que arma el pase de lista desde JavaScript
    ],
    theme: {
        extend: {
            colors: {
                // Paleta de Figma (panel admin y portada). Botones, títulos y enlaces
                // usan el color institucional; los tonos claros y las superficies se conservan.
                upp: {
                    primary: institucional,
                    'primary-container': institucional,
                    'primary-dark': aldea[900], // hover de botones y enlaces
                    'on-primary-container': '#BA8BE1',
                    'primary-fixed-dim': '#E0B7FF',
                    secondary: '#6F528A',
                    'secondary-container': '#E2BEFF',
                    surface: '#FEF7FF',
                    'surface-low': '#F9F0FF',
                    'surface-high': '#EEE5F6',
                },
                // Escala morada (módulos de alumno, tutor y pase de lista), calculada del color institucional
                aldea,
                tinta: '#1b1530', // texto principal (casi negro azulado)
                lavanda: '#fbf8ff', // fondo general de los módulos
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                heading: ['Manrope', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    // Estiliza inputs, selects y checkboxes de los formularios
    plugins: [forms],
};
