<div>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">

    {{-- Status legend --}}
    <div style="display:flex;flex-wrap:wrap;gap:0.75rem;font-size:0.8125rem;color:#64748b;align-items:center;margin-bottom:0.75rem;">
        <span style="font-weight:600;color:#374151;">Legend:</span>
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#3b82f6;margin-right:4px;vertical-align:middle;"></span>Scheduled</span>
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#d97706;margin-right:4px;vertical-align:middle;"></span>In Progress</span>
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#16a34a;margin-right:4px;vertical-align:middle;"></span>Completed</span>
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#7c3aed;margin-right:4px;vertical-align:middle;"></span>Checkout</span>
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#6b7280;margin-right:4px;vertical-align:middle;"></span>Closed</span>
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#9ca3af;margin-right:4px;vertical-align:middle;"></span>No Show</span>
    </div>

    {{-- Calendar — no wrapper card; fills the page content area --}}
    <div id="practiq-appt-calendar" wire:ignore></div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script>
    (function () {
        var CREATE_BASE_URL = @json($createBaseUrl);
        var EVENTS_URL      = @json($eventsUrl);

        function initCalendar() {
            var el = document.getElementById('practiq-appt-calendar');
            if (!el || el._fc) return;

            function persistAppointmentTime(info) {
                @this.call('updateAppointmentTime', info.event.id, info.event.start.toISOString(), info.event.end ? info.event.end.toISOString() : null)
                    .then(function () {
                        setTimeout(function () {
                            calendar.refetchEvents();
                        }, 0);
                    })
                    .catch(function () {
                        info.revert();
                    });
            }

            var calendar = new FullCalendar.Calendar(el, {
                initialView: 'timeGridWeek',
                headerToolbar: {
                    left:   'prev,next today',
                    center: 'title',
                    right:  'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                height: 'calc(100vh - 200px)',
                slotMinTime: '07:00:00',
                slotMaxTime: '20:00:00',
                allDaySlot: false,
                nowIndicator: true,
                selectable: true,
                businessHours: {
                    daysOfWeek: [1, 2, 3, 4, 5],
                    startTime:  '08:00',
                    endTime:    '18:00',
                },
                eventTimeFormat: {
                    hour:     'numeric',
                    minute:   '2-digit',
                    meridiem: 'short'
                },
                events: {
                    url:     EVENTS_URL,
                    method:  'GET',
                    failure: function () {
                        console.warn('Practiq: failed to load calendar events');
                    }
                },

                // Click on empty time slot → Create form pre-filled with that time
                dateClick: function (info) {
                    var dt  = info.date;
                    var pad = function (n) { return String(n).padStart(2, '0'); };
                    var formatted = dt.getFullYear() + '-'
                        + pad(dt.getMonth() + 1) + '-'
                        + pad(dt.getDate()) + ' '
                        + pad(dt.getHours()) + ':'
                        + pad(dt.getMinutes()) + ':00';
                    window.location.href = CREATE_BASE_URL + '?start_datetime=' + encodeURIComponent(formatted);
                },

                editable: true,

                // Drag to reschedule
                eventDrop: function (info) {
                    persistAppointmentTime(info);
                },

                // Resize to change duration
                eventResize: function (info) {
                    persistAppointmentTime(info);
                },

                // Click on existing event → View page
                eventClick: function (info) {
                    info.jsEvent.preventDefault();
                    if (info.event.url) {
                        window.location.href = info.event.url;
                    }
                },

                // Tooltip on hover: patient · type · status
                eventDidMount: function (info) {
                    var type   = info.event.extendedProps.appointmentType || '';
                    var status = info.event.extendedProps.status || '';
                    var parts  = [info.event.title];
                    if (type)   parts.push(type);
                    if (status) parts.push('(' + status.replace(/_/g, ' ') + ')');
                    info.el.title = parts.join(' · ');
                },
            });

            calendar.render();
            el._fc = calendar;
        }

        document.addEventListener('DOMContentLoaded', initCalendar);
        document.addEventListener('livewire:navigated', function () {
            var el = document.getElementById('practiq-appt-calendar');
            if (el) { el._fc = null; }
            initCalendar();
        });
    })();
    </script>
</div>
