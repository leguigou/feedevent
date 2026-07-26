import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

/**
 * Initialise le calendrier FullCalendar avec les événements
 */
export async function initCalendar(elementId = 'calendar') {
    const el = document.getElementById(elementId);
    if (!el) return;

    try {
        const res = await fetch('/api/events?per_page=500');
        const data = await res.json();
        const events = data.data || data;

        const isMobile = window.innerWidth < 640;

        const calendar = new Calendar(el, {
            plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
            initialView: isMobile ? 'listMonth' : 'dayGridMonth',
            locale: 'fr',
            firstDay: 1,
            height: 'auto',
            headerToolbar: {
                left: isMobile ? 'prev,next' : 'prev,next today',
                center: 'title',
                right: isMobile ? 'listMonth' : 'dayGridMonth,timeGridWeek,listMonth',
            },
            buttonText: {
                today: "Aujourd'hui",
                month: 'Mois',
                week: 'Semaine',
                list: 'Liste',
            },
            events: events.map(e => ({
                id: e.id,
                title: e.title,
                start: e.date_start,
                end: e.date_end || undefined,
                backgroundColor: e.category?.color || '#7c3aed',
                borderColor: 'transparent',
                textColor: '#fff',
                extendedProps: {
                    location: e.location,
                    description: e.description,
                    price: e.price,
                    icon: e.category?.icon || '📌',
                },
            })),
            eventContent: function(arg) {
                return {
                    html: `<div class="flex items-center gap-1 p-0.5 text-xs" style="color:white">
                        <span>${arg.event.extendedProps.icon || ''}</span>
                        <span class="truncate font-medium">${arg.event.title}</span>
                    </div>`,
                };
            },
            eventClick: function(info) {
                info.jsEvent.preventDefault();
                const e = info.event;
                const p = e.extendedProps;
                alert([
                    `🎉 ${e.title}`,
                    '',
                    `📅 ${e.start.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })}`,
                    e.end ? `→ ${e.end.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit' })}` : '',
                    p.location ? `📍 ${p.location}` : '',
                    p.price ? `💰 ${p.price}€` : '',
                    '',
                    p.description ? p.description.slice(0, 300) : '',
                ].filter(Boolean).join('\n'));
            },
        });

        calendar.render();
        console.log(`FullCalendar chargé avec ${events.length} événements`);
        return calendar;

    } catch (err) {
        console.error('Erreur calendrier:', err);
        el.innerHTML = `
            <div class="flex flex-col items-center justify-center py-16 text-gray-400 dark:text-gray-500">
                <span class="text-5xl mb-4">📅</span>
                <p class="text-lg font-medium mb-1">Impossible de charger le calendrier</p>
                <p class="text-sm">Vérifie ta connexion et réessaie</p>
                <button onclick="location.reload()" 
                    class="mt-4 px-4 py-2 bg-brand-500 text-white rounded-xl hover:bg-brand-600 text-sm font-medium">
                    🔄 Réessayer
                </button>
            </div>`;
    }
}

// Auto-init au chargement
// Note : type="module" est deferred, DOMContentLoaded déjà passé
initCalendar();
