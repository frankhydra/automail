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

                // Brand colours come from CSS variables so the Themes feature can swap them
                // (values: resources/css/themes.css). Default theme = rust.
                // Brand: rust is the main accent. "wax" is kept as a name because older
                // views use it for small dots/underlines; it is now the warm amber.
                accent: 'rgb(var(--accent) / <alpha-value>)',
                wax: 'rgb(var(--wax) / <alpha-value>)',
                sun: 'rgb(var(--sun) / <alpha-value>)',
                'sun-tint': 'rgb(var(--sun-tint) / <alpha-value>)',

                // Sidebar is charcoal in BOTH light and dark mode.
                sidebar: 'rgb(var(--sidebar) / <alpha-value>)',
                'sidebar-hover': 'rgb(var(--sidebar-hover) / <alpha-value>)',
                'sidebar-line': 'rgb(var(--sidebar-line) / <alpha-value>)',
                'sidebar-text': 'rgb(var(--sidebar-text) / <alpha-value>)',

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
                    50: 'rgb(var(--accent-50) / <alpha-value>)',
                    100: 'rgb(var(--accent-100) / <alpha-value>)',
                    200: 'rgb(var(--accent-200) / <alpha-value>)',
                    300: 'rgb(var(--accent-300) / <alpha-value>)',
                    400: 'rgb(var(--accent-400) / <alpha-value>)',
                    500: 'rgb(var(--accent-500) / <alpha-value>)',
                    600: 'rgb(var(--accent-600) / <alpha-value>)',
                    700: 'rgb(var(--accent-700) / <alpha-value>)',
                    800: 'rgb(var(--accent-800) / <alpha-value>)',
                    900: 'rgb(var(--accent-900) / <alpha-value>)',
                },
            },
            boxShadow: {
                soft: '0 1px 2px rgba(29,30,34,0.04), 0 4px 16px rgba(29,30,34,0.05)',
            },
        },
    },

    plugins: [forms],
};
