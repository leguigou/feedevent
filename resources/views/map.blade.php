<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">
                🗺️ Carte des événements
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('home') }}" class="btn-ghost text-sm">← Feed</a>
                <a href="{{ route('calendar') }}" class="btn-ghost text-sm">📅</a>
            </div>
        </div>
    </x-slot>

    <div class="py-0 sm:py-4">
        <div class="sm:max-w-7xl sm:mx-auto sm:px-6 lg:px-8">
            <div class="event-card !rounded-none sm:!rounded-2xl overflow-hidden" style="height: calc(100vh - 8rem); min-height: 400px;">
                <div id="map" style="height: 100%; width: 100%;"></div>
            </div>
        </div>
    </div>

    @push('scripts-bottom')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            const res = await fetch('/api/events?per_page=500');
            const data = await res.json();
            const allEvents = data.data || data;
            const events = allEvents.filter(e => e.latitude && e.longitude);

            const map = L.map('map').setView([48.8566, 2.3522], 6);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap'
            }).addTo(map);

            // Custom marker icons par catégorie
            const icons = {};
            events.forEach(e => {
                if (e.category && !icons[e.category.id]) {
                    icons[e.category.id] = L.divIcon({
                        html: `<div style="background:${e.category.color || '#7c3aed'}; width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:18px; box-shadow:0 2px 8px rgba(0,0,0,0.3); border:2px solid white;">${e.category.icon || '📌'}</div>`,
                        className: '',
                        iconSize: [36, 36],
                        iconAnchor: [18, 18],
                        popupAnchor: [0, -20]
                    });
                }
            });

            events.forEach(e => {
                const icon = icons[e.category?.id] || L.divIcon({
                    html: `<div style="background:#7c3aed; width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:18px; box-shadow:0 2px 8px rgba(0,0,0,0.3); border:2px solid white;">📌</div>`,
                    className: '',
                    iconSize: [36, 36],
                    iconAnchor: [18, 18],
                    popupAnchor: [0, -20]
                });

                const marker = L.marker([e.latitude, e.longitude], { icon }).addTo(map);
                
                const priceHtml = e.price ? `<div style="margin-top:8px;"><span style="background:#22c55e; color:white; padding:2px 10px; border-radius:8px; font-size:12px; font-weight:bold;">${e.price}€</span></div>` : '';
                
                marker.bindPopup(`
                    <div style="min-width:220px; max-width:300px; font-family:system-ui,sans-serif;">
                        <div style="font-size:12px; color:#7c3aed; font-weight:600; margin-bottom:4px;">
                            ${e.category ? e.category.icon + ' ' + e.category.name : '📌 Événement'}
                        </div>
                        <div style="font-size:15px; font-weight:700; margin-bottom:6px;">${e.title}</div>
                        <div style="font-size:12px; color:#666; margin-bottom:4px;">
                            📅 ${new Date(e.date_start).toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })}
                        </div>
                        ${e.location ? `<div style="font-size:12px; color:#666; margin-bottom:4px;">📍 ${e.location}</div>` : ''}
                        ${priceHtml}
                        ${e.description ? `<div style="font-size:12px; color:#888; margin-top:6px; border-top:1px solid #eee; padding-top:6px;">${e.description.slice(0, 150)}${e.description.length > 150 ? '...' : ''}</div>` : ''}
                    </div>
                `, { maxWidth: 320, className: 'dark:bg-gray-900 dark:text-white' });
            });

            // Ajuster la vue aux marqueurs
            if (events.length > 0) {
                const group = L.featureGroup(events.map(e => L.marker([e.latitude, e.longitude])));
                map.fitBounds(group.getBounds().pad(0.1));
            }

            // Re-rendre sur resize
            window.addEventListener('resize', () => map.invalidateSize());
        });
    </script>
    <style>
        .leaflet-popup-content-wrapper { border-radius: 16px !important; padding: 4px !important; }
        .dark .leaflet-popup-content-wrapper { background: #1a1a2e !important; color: #e5e5e5 !important; }
        .dark .leaflet-popup-tip { background: #1a1a2e !important; }
        .leaflet-container { font-family: system-ui, sans-serif !important; }
    </style>
    @endpush
</x-app-layout>
