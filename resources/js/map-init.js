import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';

const mapElement = document.getElementById('map');

if (mapElement) {
    const map = L.map(mapElement, { zoomControl: false }).setView([46.7, 2.5], 6);
    L.control.zoom({ position: 'bottomright' }).addTo(map);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap',
    }).addTo(map);

    const cluster = L.markerClusterGroup({
        showCoverageOnHover: false,
        maxClusterRadius: 48,
        spiderfyOnMaxZoom: true,
    }).addTo(map);
    const categorySelect = document.getElementById('map-category');
    const searchAreaButton = document.getElementById('map-search-area');
    const status = document.getElementById('map-status');
    const list = document.getElementById('map-event-list');
    const count = document.getElementById('map-count');
    const empty = document.getElementById('map-empty');
    let allEvents = [];
    let hasMoved = false;

    const text = (tag, value, className = '') => {
        const node = document.createElement(tag);
        node.textContent = value || '';
        node.className = className;
        return node;
    };

    const eventCard = (event, compact = false) => {
        const link = document.createElement('a');
        link.href = `/events/${event.id}`;
        link.className = compact
            ? 'block rounded-xl border border-gray-200 p-3 transition hover:border-brand-300 hover:bg-brand-50 dark:border-gray-800 dark:hover:bg-brand-950/20'
            : 'block min-w-[220px] max-w-[280px] p-1';
        link.append(text('p', event.category?.name || 'Événement', 'text-xs font-bold text-brand-600'));
        link.append(text('h2', event.title, 'mt-1 text-sm font-black text-gray-950 dark:text-white'));
        const date = new Intl.DateTimeFormat('fr-FR', { weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }).format(new Date(event.date_start));
        link.append(text('p', date, 'mt-2 text-xs text-gray-500'));
        link.append(text('p', event.location || 'Lieu à confirmer', 'mt-1 truncate text-xs text-gray-500'));
        return link;
    };

    const markerIcon = event => {
        const icon = document.createElement('span');
        icon.textContent = event.category?.icon || '✦';
        icon.setAttribute('aria-hidden', 'true');
        return L.divIcon({
            html: icon,
            className: 'feedevent-marker',
            iconSize: [42, 42],
            iconAnchor: [21, 21],
            popupAnchor: [0, -22],
        });
    };

    const render = (useBounds = false) => {
        cluster.clearLayers();
        list.replaceChildren();
        const category = categorySelect.value;
        const visible = allEvents.filter(event => {
            if (category && String(event.category_id) !== category) return false;
            if (!event.latitude || !event.longitude) return false;
            return !useBounds || map.getBounds().contains([event.latitude, event.longitude]);
        });

        visible.forEach(event => {
            const marker = L.marker([event.latitude, event.longitude], { icon: markerIcon(event), title: event.title });
            const popup = document.createElement('div');
            popup.append(eventCard(event));
            marker.bindPopup(popup, { maxWidth: 300 });
            cluster.addLayer(marker);
            list.append(eventCard(event, true));
        });

        count.textContent = String(visible.length);
        empty.classList.toggle('hidden', visible.length > 0);
        if (!useBounds && visible.length) map.fitBounds(cluster.getBounds().pad(.12), { maxZoom: 14 });
    };

    const load = async () => {
        try {
            const response = await fetch('/api/events?per_page=100', { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error();
            const data = await response.json();
            allEvents = data.data || [];
            render();
            status.hidden = true;
        } catch (_) {
            status.textContent = 'Impossible de charger les événements.';
        }
    };

    categorySelect.addEventListener('change', () => render());
    map.on('moveend', () => {
        if (!hasMoved) {
            hasMoved = true;
            return;
        }
        searchAreaButton.classList.remove('hidden');
    });
    searchAreaButton.addEventListener('click', () => {
        render(true);
        searchAreaButton.classList.add('hidden');
    });
    document.getElementById('map-locate').addEventListener('click', () => {
        map.locate({ setView: true, maxZoom: 13 });
    });
    map.on('locationerror', () => {
        status.hidden = false;
        status.textContent = 'Localisation non disponible.';
        setTimeout(() => status.hidden = true, 3000);
    });

    load();
}
