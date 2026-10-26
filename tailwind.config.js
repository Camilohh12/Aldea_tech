import defaultTheme from 'tailwindcss/defaultTheme';

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
                }
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                heading: ['Manrope', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [],
};