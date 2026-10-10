import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Manrope', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Morado institucional UPP (tomado del escudo)
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
                lavanda: '#fbf8ff', // fondo general
            },
        },
    },

    plugins: [forms],
};
