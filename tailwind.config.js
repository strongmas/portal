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
            colors: {
                // ── Remapped legacy tokens → new dark values ──────────────
                paper:      '#0A1428',   // page background (deep navy)
                'paper-2':  '#0F1B33',   // card base
                ink:        '#F0F4FA',   // primary text (near-white)
                'ink-soft': '#B5C4DD',   // secondary text
                'ink-mute': '#7A8AA5',   // dim/tertiary text
                hi:         '#EFC59D',   // tan accent (was yellow)
                'hi-soft':  '#142B54',   // raised card bg (for card-hi)
                note:       '#F47272',   // danger/alert red

                // ── New dark design tokens ─────────────────────────────────
                navy: {
                    950: '#0A1428',
                    900: '#0F1B33',
                    800: '#142B54',
                    700: '#1B3668',
                    600: '#2A4885',
                },
                tan: {
                    300: '#F5D5B5',
                    400: '#EFC59D',
                    500: '#D4A77A',
                },
                ok:      '#5DD9A7',
                warn:    '#F5C26B',
                danger:  '#F47272',
            },
            fontFamily: {
                sans: ['Geist', 'Inter', ...defaultTheme.fontFamily.sans],
                mono: ['Geist Mono', 'JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },
            borderRadius: {
                'pill': '999px',
            },
        },
    },

    plugins: [forms],
};
