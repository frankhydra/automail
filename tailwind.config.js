import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class', // Prevents OS dark mode from overriding the custom warm paper palette
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                ink: '#241F1B',
                paper: '#F7F3EC',
                'paper-tint': '#EFE8D8',
                card: '#FDFBF7',
                muted: '#6B6255',
                accent: '#2B3A67',
                wax: '#8C2F39',
                border: '#D9D0C1',
            },
        },
    },

    plugins: [forms],
};