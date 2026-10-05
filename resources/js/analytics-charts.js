import Chart from 'chart.js/auto';

const dataElement = document.getElementById('analytics-chart-data');
const canvas = document.getElementById('hourlyChart');

if (dataElement && canvas) {
    const data = JSON.parse(dataElement.textContent);
    let chart = null;

    function palette() {
        const dark = document.documentElement.classList.contains('dark');

        return dark
            ? { opens: '#D98258', clicks: '#F5E2CF', grid: 'rgba(245, 226, 207, 0.08)', tick: '#B8A793' }
            : { opens: '#B85D33', clicks: '#1D1E22', grid: 'rgba(29, 30, 34, 0.07)', tick: '#6A5A4B' };
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
