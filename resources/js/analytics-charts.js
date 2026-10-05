import Chart from 'chart.js/auto';
import { themeColors } from './theme-colors';

const dataElement = document.getElementById('analytics-chart-data');
const canvas = document.getElementById('hourlyChart');

if (dataElement && canvas) {
    const data = JSON.parse(dataElement.textContent);
    let chart = null;

    function palette() {
        const t = themeColors();

        return { opens: t.accent, clicks: t.ink, grid: t.grid, tick: t.muted };
    }

    function draw() {
        const colors = palette();

        if (chart) {
            chart.destroy();
        }

        chart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [
                    { label: 'Opens', data: data.opens, backgroundColor: colors.opens, borderRadius: 4 },
                    { label: 'Clicks', data: data.clicks, backgroundColor: colors.clicks, borderRadius: 4 },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { labels: { usePointStyle: true, boxWidth: 8, color: colors.tick } } },
                scales: {
                    x: { grid: { display: false }, border: { display: false }, ticks: { color: colors.tick, maxRotation: 0, autoSkip: true } },
                    y: { beginAtZero: true, border: { display: false }, grid: { color: colors.grid }, ticks: { precision: 0, color: colors.tick } },
                },
            },
        });
    }

    draw();

    // Redraw when the dark-mode toggle flips the class on <html>.
    new MutationObserver(draw).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
}
