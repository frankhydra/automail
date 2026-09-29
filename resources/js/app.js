import Alpine from 'alpinejs';

// Self-hosted font, bundled by Vite: pages no longer wait on a third-party font server.
import '@fontsource/figtree/400.css';
import '@fontsource/figtree/500.css';
import '@fontsource/figtree/600.css';
import '@fontsource/figtree/700.css';
import '@fontsource/figtree/800.css';

window.Alpine = Alpine;

Alpine.start();

// Charts only exist on the dashboard, so the chart library is split into its own
// file and downloaded only when a page actually contains chart data.
if (document.getElementById('dashboard-chart-data')) {
    import('./dashboard-charts');
}
