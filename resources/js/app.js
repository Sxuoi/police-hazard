import Alpine from 'alpinejs';

// ── Alpine.js ────────────────────────────────────────────────────────
window.Alpine = Alpine;

// Conditionally load officer or admin modules based on URL
if (window.location.pathname.startsWith('/officer')) {
    // Officer mobile UI — register officer Alpine components
    // Officer screens don't use Leaflet for the admin dashboard map.
    import('./officer/officerApp.js').then(({ registerOfficerComponents }) => {
        registerOfficerComponents();
        Alpine.start();
    });
} else {
    // Admin UI — Leaflet must be available globally BEFORE Alpine starts,
    // because Blade views (dashboard, locations) reference `L.map(...)` inside
    // Alpine `init()` hooks that fire as soon as Alpine.start() runs.
    //
    // Using a static import ensures `window.L` is defined synchronously
    // before any Alpine component touches it.
    import('leaflet').then((L) => {
        window.L = L.default;
        return import('leaflet.markercluster');
    }).then(() => {
        // Global Alpine store for sidebar + notifications
        Alpine.store('sidebar', { open: window.innerWidth >= 1024 });
        Alpine.store('notifications', { count: 0, items: [] });

        Alpine.start();
    });
}
