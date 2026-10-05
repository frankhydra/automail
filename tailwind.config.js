import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
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
                // Theme-aware colors: values live in resources/css/app.css and flip in ".dark".
                ink: 'var(--ink)',
                paper: 'var(--paper)',
                'paper-tint': 'var(--paper-tint)',
                card: 'var(--card)',
                muted: 'var(--muted)',
                border: 'var(--border)',

                // Brand: rust is the main accent. "wax" is kept as a name because older
                // views use it for small dots/underlines; it is now the warm amber.
                accent: '#B85D33',
                wax: '#E9A24F',
                sun: '#E9A24F',
                'sun-tint': '#F6D2A1',

                // Sidebar is charcoal in BOTH light and dark mode.
                sidebar: '#1D1E22',
                'sidebar-hover': '#2A2B31',
                'sidebar-line': '#34353B',
                'sidebar-text': '#D8C7B3',

                // Status colors (static hex so opacity modifiers like bg-success/20 work).
                success: '#3F6B4C',
                'success-tint': '#E4EEE4',
                warning: '#B8791F',
                'warning-tint': '#FBEBCF',
                danger: '#A63A2E',
                'danger-tint': '#F6E0DB',
                info: '#7E6A5E',
                'info-tint': '#EFE3D4',

                // Older pages still use Tailwind's "indigo" classes. Pointing indigo at the
                // rust ramp re-colors all of them until each page is redesigned.
                indigo: {
                    50: '#FBF1EB',
                    100: '#F5DFD2',
                    200: '#EBC0A8',
                    300: '#DE9A76',
                    400: '#CF7A4E',
                    500: '#C46A3E',
                    600: '#B85D33',
                    700: '#984B28',
                    800: '#78391E',
                    900: '#552814',
                },
            },
            boxShadow: {
                soft: '0 1px 2px rgba(29,30,34,0.04), 0 4px 16px rgba(29,30,34,0.05)',
            },
        },
    },

    plugins: [forms],
};
