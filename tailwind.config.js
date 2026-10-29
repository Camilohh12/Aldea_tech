import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                // Colores institucionales de Figma (panel admin y portada)
                upp: {
                    primary: '#340158',
                    'primary-container': '#4B1F6F',
                    'on-primary-container': '#BA8BE1',
                    'primary-fixed-dim': '#E0B7FF',
                    secondary: '#6F528A',
                    'secondary-container': '#E2BEFF',
                    surface: '#FEF7FF',
                    'surface-low': '#F9F0FF',
                    'surface-high': '#EEE5F6',
                },
                // Escala morada del módulo alumno / tutor (tomada del escudo)
                aldea: {
                    50: '#f6f2fa',
                    100: '#ece4f5',
                    200: '#d7c8ea',
                    300: '#b49bd6',
                    400: '#8a66bb',
                    500: '#6a3fa3',
                    600: '#5a2d93',
                    700: '#4a1f80',
                    800: '#3b1270',
                    900: '#26093f',
                },
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
