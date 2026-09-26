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
                // These read from CSS custom properties (see resources/css/app.css),
                // which flip value inside ".dark" - so any page using bg-card,
                // text-ink, bg-paper, border-border, etc. gets dark mode
                // automatically, with no separate dark: class needed per page.
                ink: 'var(--ink)',
                paper: 'var(--paper)',
                'paper-tint': 'var(--paper-tint)',
                card: 'var(--card)',
                muted: 'var(--muted)',
                border: 'var(--border)',
                // Accent/wax stay the same in both themes - a brand color that
                // doesn't need to invert.
                accent: '#2B3A67',
                wax: '#8C2F39',
                // Status/feedback colors: plain static hex (not CSS variables) so
                // Tailwind's opacity modifiers (bg-success/20, text-danger/70, etc.)
                // work - a CSS-variable color can't be alpha-blended at build time.
                // Chosen to sit alongside the warm palette rather than clash with
                // stock Tailwind red/green/amber.
                success: '#3F6B4C',
                'success-tint': '#E6EDE7',
                warning: '#B8863B',
                'warning-tint': '#F5ECDD',
                danger: '#8C2F39',
                'danger-tint': '#F3E4E3',
                info: '#2B3A67',
                'info-tint': '#E7EAF1',
            },
        },
    },

    plugins: [forms],
};
