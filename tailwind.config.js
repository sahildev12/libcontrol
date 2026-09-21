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
                sans: ['"Plus Jakarta Sans"', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    yellow: '#fecf25',
                    blue: '#243a8b',
                    navy: '#0c0048',
                },
            },
            boxShadow: {
                'lc-card': '0 10px 30px -12px rgb(12 0 72 / 0.18)',
            },
        },
    },

    plugins: [forms],
};
