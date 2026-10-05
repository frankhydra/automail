import Chart from 'chart.js/auto';

const dataElement = document.getElementById('dashboard-chart-data');

if (dataElement) {
    const data = JSON.parse(dataElement.textContent);
    let charts = [];

    // Brand colors (rust / charcoal / amber), lighter variants in dark mode.
    function palette() {
        const dark = document.documentElement.classList.contains('dark');

        return dark
            ? { accent: '#D98258', second: '#F5E2CF', sun: '#E9A24F', grid: 'rgba(245, 226, 207, 0.08)', tick: '#B8A793' }
            : { accent: '#B85D33', second: '#1D1E22', sun: '#E9A24F', grid: 'rgba(29, 30, 34, 0.07)', tick: '#6A5A4B' };
    }

    // Fades a line's fill from a translucent color at the top to nothing at the bottom.
    function fill(hex) {
        return (context) => {
            const { ctx, chartArea } = context.chart;

            if (!chartArea) {
                return hex + '22';
            }

            const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
            gradient.addColorStop(0, hex + '44');
            gradient.addColorStop(1, hex + '00');

            return gradient;
        };
    }

    function baseOptions(colors) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, border: { display: false }, ticks: { color: colors.tick } },
                y: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: colors.grid },
                    ticks: { precision: 0, color: colors.tick },
                },
            },
        };
    }

    function render() {
        charts.forEach((chart) => chart.destroy());
        charts = [];

        const colors = palette();
        Chart.defaults.font.family = 'Figtree, ui-sans-serif, system-ui, sans-serif';

        const volume = document.getElementById('volumeChart');

        if (volume) {
            charts.push(new Chart(volume, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Emails sent',
                        data: data.sent,
                        backgroundColor: colors.accent,
                        borderRadius: 8,
                        borderSkipped: false,
                        maxBarThickness: 34,
                    }],
                },
                options: baseOptions(colors),
            }));
        }

        const engagement = document.getElementById('engagementChart');

        if (engagement) {
            const options = baseOptions(colors);
            options.plugins.legend = {
                display: true,
                position: 'bottom',
                labels: { usePointStyle: true, boxWidth: 8, color: colors.tick },
            };

            const line = (label, values, color) => ({
                label,
                data: values,
                borderColor: color,
                backgroundColor: fill(color),
                borderWidth: 2.5,
                fill: true,
                tension: 0.35,
                pointRadius: 0,
                pointHoverRadius: 5,
            });

            charts.push(new Chart(engagement, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [
                        line('Opens', data.opens, colors.accent),
                        line('Clicks', data.clicks, colors.sun),
                    ],
                },
                options,
            }));
        }
    }

    render();

    // Redraw with the right colors when the dark-mode toggle flips the <html> class.
    new MutationObserver(() => render()).observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
}
