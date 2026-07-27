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
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Azul corporativo GRUPO CODISE
                brand: {
                    50: '#f0f6fe',
                    100: '#dde9fc',
                    200: '#c3d9fa',
                    300: '#9ac1f6',
                    400: '#6aa0ef',
                    500: '#477ee8',
                    600: '#3261dc',
                    700: '#294dca',
                    800: '#2740a4',
                    900: '#253a82',
                    950: '#1b254f',
                },
            },
        },
    },

    plugins: [forms],
};
