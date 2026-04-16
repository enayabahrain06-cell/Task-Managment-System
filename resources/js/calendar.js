import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    if (calendarEl) {
        const calendar = new Calendar(calendarEl, {
            plugins: [ dayGridPlugin, timeGridPlugin, interactionPlugin ],
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek'
            },
            events: calendarEl.dataset.events ? JSON.parse(calendarEl.dataset.events) : [],
            eventClick: function(info) {
                info.jsEvent.preventDefault();
                const event = info.event.extendedProps;
                if (event.type === 'task') {
                    window.location.href = `/user/tasks/${event.id}`;
                }
            },
            editable: true,
            selectable: true,
            select: function(info) {
                // Add manual event logic
            }
        });
        calendar.render();
    }
});

