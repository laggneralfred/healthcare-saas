<div>
    {{-- FullCalendar CSS & JS from CDN --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">

    {{-- Toolbar: New Appointment button --}}
    <div style="display:flex;justify-content:flex-end;margin-bottom:1rem;">
        <a href="{{ \App\Filament\Resources\Appointments\AppointmentResource::getUrl('create') }}"
           style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.5rem 1.125rem;background:#0d9488;color:#ffffff;font-size:0.875rem;font-weight:600;border-radius:0.5rem;text-decoration:none;">
            + New Appointment
        </a>
    </div>

    {{-- Calendar container --}}
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:0.75rem;padding:1.25rem;">
        <div id="practiq-fullcalendar" style="min-height:600px;"></div>
    </div>

    {{-- Status legend --}}
    <div style="display:flex;flex-wrap:wrap;gap:1rem;margin-top:0.75rem;font-size:0.8125rem;color:#64748b;">
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#3b82f6;margin-right:4px;vertical-align:middle;"></span>Scheduled</span>
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#d97706;margin-right:4px;vertical-align:middle;"></span>In Progress</span>
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#16a34a;margin-right:4px;vertical-align:middle;"></span>Completed</span>
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#7c3aed;margin-right:4px;vertical-align:middle;"></span>Checkout</span>
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#6b7280;margin-right:4px;vertical-align:middle;"></span>Closed</span>
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#ef4444;margin-right:4px;vertical-align:middle;"></span>Cancelled</span>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script>
    (function () {
        function initPractiqCalendar() {
            var el = document.getElementById('practiq-fullcalendar');
            if (!el || el._fc) return;

            var calendar = new FullCalendar.Calendar(el, {
                initialView: 'timeGridWeek',
                headerToolbar: {
                    left:   'prev,next today',
                    center: 'title',
                    right:  'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                height: 'auto',
                slotMinTime: '07:00:00',
                slotMaxTime: '20:00:00',
                allDaySlot: false,
                nowIndicator: true,
                businessHours: {
                    daysOfWeek: [1, 2, 3, 4, 5],
                    startTime: '08:00',
                    endTime: '18:00',
                },
                eventTimeFormat: {
                    hour:   'numeric',
                    minute: '2-digit',
                    meridiem: 'short'
                },
                events: {
                    url: '{{ route('admin.calendar.events') }}',
                    method: 'GET',
                    extraParams: {},
                    failure: function () {
                        console.warn('Practiq: failed to load calendar events');
                    }
                },
                eventClick: function (info) {
                    info.jsEvent.preventDefault();
                    if (info.event.url) {
                        window.location.href = info.event.url;
                    }
                },
                eventDidMount: function (info) {
                    var status = info.event.extendedProps.status ?? '';
                    info.el.title = info.event.title + (status ? ' (' + status + ')' : '');
                },
            });

            calendar.render();
            el._fc = calendar;
        }

        document.addEventListener('DOMContentLoaded', initPractiqCalendar);
        document.addEventListener('livewire:navigated', initPractiqCalendar);
    })();
    </script>
</div>
