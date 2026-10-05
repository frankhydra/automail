// Reads the ACTIVE theme's colours from the CSS variables, so charts follow the theme.
// Usage:  import { themeColors } from './theme-colors';  const t = themeColors();

function channelsToHex(value) {
    const parts = value.trim().split(/\s+/).map(Number);
    if (parts.length < 3 || parts.some(Number.isNaN)) {
        return null;
    }
    return '#' + parts.slice(0, 3).map((n) => n.toString(16).padStart(2, '0')).join('').toUpperCase();
}

function hexToRgba(hex, alpha) {
    const n = parseInt(hex.slice(1), 16);
    return `rgba(${(n >> 16) & 255}, ${(n >> 8) & 255}, ${n & 255}, ${alpha})`;
}

export function themeColors() {
    const styles = getComputedStyle(document.documentElement);
    const dark = document.documentElement.classList.contains('dark');
    const brand = (name, fallback) => channelsToHex(styles.getPropertyValue(name)) || fallback;
    const plain = (name, fallback) => styles.getPropertyValue(name).trim() || fallback;

    const ink = plain('--ink', '#1D1E22');

    return {
        dark,
        // The accent is lightened a step in dark mode for readability.
        accent: dark ? brand('--accent-400', '#D98258') : brand('--accent', '#B85D33'),
        sun: brand('--sun', '#E9A24F'),
        ink,
        muted: plain('--muted', '#6A5A4B'),
        grid: hexToRgba(ink, dark ? 0.08 : 0.07),
    };
}
