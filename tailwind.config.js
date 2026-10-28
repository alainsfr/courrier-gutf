import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            colors: {
                brand: {DEFAULT: 'var(--gutf-primary)', soft: 'color-mix(in srgb,var(--gutf-primary) 10%,white)'},
                accent: {DEFAULT: 'var(--gutf-accent)', soft: 'color-mix(in srgb,var(--gutf-accent) 10%,white)'},
            },
            fontFamily: {
                sans: ['Segoe UI', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
