import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import frLocale from '@fullcalendar/core/locales/fr';

const calendarElement = document.getElementById('calendar');

if (calendarElement) {
    const renderError = () => {
        const wrapper = document.createElement('div');
        wrapper.className = 'flex flex-col items-center justify-center py-16 text-center';
        const title = document.createElement('p');
        title.className = 'text-lg font-black';
        title.textContent = 'Impossible de charger l’agenda';
        const message = document.createElement('p');
        message.className = 'mt-1 text-sm text-gray-500';
        message.textContent = 'Vérifie ta connexion puis réessaie.';
        const retry = document.createElement('button');
        retry.className = 'btn-primary mt-5';
        retry.textContent = 'Réessayer';
        retry.addEventListener('click', () => window.location.reload());
        wrapper.append(title, message, retry);
        calendarElement.replaceChildren(wrapper);
    };

    fetch('/api/events?per_page=100', { headers: { Accept: 'application/json' } })
        .then(response => {
            if (!response.ok) throw new Error();
            return response.json();
        })
        .then(data => {
            const isMobile = window.matchMedia('(max-width: 639px)').matches;
            const calendar = new Calendar(calendarElement, {
                plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
                locales: [frLocale],
                locale: 'fr',
                initialView: isMobile ? 'listMonth' : 'dayGridMonth',
                firstDay: 1,
                height: 'auto',
                headerToolbar: {
                    left: isMobile ? 'prev,next' : 'prev,next today',
                    center: 'title',
                    right: isMobile ? '' : 'dayGridMonth,timeGridWeek,listMonth',
                },
                buttonText: { today: 'Aujourd’hui', month: 'Mois', week: 'Semaine', list: 'Liste' },
                events: (data.data || []).map(event => ({
                    id: event.id,
                    title: event.title,
                    start: event.date_start,
                    end: event.date_end || undefined,
                    url: `/events/${event.id}`,
                    backgroundColor: event.category?.color || '#7c3aed',
                    borderColor: 'transparent',
                    extendedProps: {
                        icon: event.category?.icon || '✦',
                        location: event.location || 'Lieu à confirmer',
                    },
                })),
                eventContent: info => {
                    const wrapper = document.createElement('span');
                    wrapper.className = 'flex min-w-0 items-center gap-1.5 text-xs font-bold';
                    const icon = document.createElement('span');
                    icon.textContent = info.event.extendedProps.icon;
                    icon.setAttribute('aria-hidden', 'true');
                    const title = document.createElement('span');
                    title.className = 'truncate';
                    title.textContent = info.event.title;
                    wrapper.append(icon, title);
                    return { domNodes: [wrapper] };
                },
                eventClick: info => {
                    info.jsEvent.preventDefault();
                    window.location.href = info.event.url;
                },
                windowResize: view => {
                    const mobile = window.matchMedia('(max-width: 639px)').matches;
                    if (mobile && view.view.type !== 'listMonth') view.changeView('listMonth');
                },
            });
            calendar.render();
            calendarElement.querySelectorAll('.fc-icon[role="img"]').forEach(icon => {
                icon.removeAttribute('role');
                icon.setAttribute('aria-hidden', 'true');
            });
        })
        .catch(renderError);
}
