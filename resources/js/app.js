import Alpine from 'alpinejs';
import L from 'leaflet';
import 'leaflet.markercluster';

// ── Alpine.js ────────────────────────────────────────────────────────
window.Alpine = Alpine;

// Global Alpine store for sidebar + notifications
Alpine.store('sidebar', { open: window.innerWidth >= 1024 });
Alpine.store('notifications', { count: 0, items: [] });

Alpine.start();

// ── Leaflet.js ───────────────────────────────────────────────────────
window.L = L;
