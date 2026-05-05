<div>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
    <style>
        .practiq-calendar-mobile-list { display: none; }
        .practiq-calendar-event { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
        .practiq-calendar-event-title { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .practiq-calendar-badges { display: flex; flex-wrap: wrap; gap: 3px; line-height: 1; }
        .practiq-calendar-badge { display: inline-flex; align-items: center; border-radius: 9999px; padding: 1px 5px; font-size: 10px; font-weight: 600; max-width: 100%; }
        @media (max-width: 768px) {
            .practiq-calendar-mobile-list { display: block; }
            #practiq-appt-calendar { min-height: 560px; }
            .fc .fc-toolbar { align-items: flex-start; flex-direction: column; gap: 8px; }
        }
    </style>

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

    <div class="practiq-calendar-mobile-list" style="background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:12px;overflow:hidden;">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 16px;border-bottom:1px solid #e5e7eb;">
            <h2 style="margin:0;font-size:15px;font-weight:700;color:#111827;">Today’s Appointments</h2>
            <span style="font-size:12px;color:#6b7280;">{{ $todayAppointments->count() }}</span>
        </div>
        <div style="padding:10px 14px;">
            @forelse($todayAppointments as $appointment)
                @php
                    $careStatus = $appointment->patient?->getAttribute('care_status_summary');
                    $careStyle = match($careStatus['color'] ?? 'gray') {
                        'success' => 'background:#d1fae5;color:#065f46;',
                        'warning' => 'background:#fef3c7;color:#92400e;',
                        'danger' => 'background:#fee2e2;color:#991b1b;',
                        'info' => 'background:#e0f2fe;color:#0c4a6e;',
                        'primary' => 'background:#dbeafe;color:#1e40af;',
                        default => 'background:#f3f4f6;color:#374151;',
                    };
                    $language = $appointment->patient?->preferred_language ?? 'en';
                @endphp
                <div style="display:flex;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid #f3f4f6;">
                    <div style="min-width:0;">
                        <div style="font-size:12px;color:#6b7280;">{{ $appointment->start_datetime?->format('g:i A') ?? '—' }} · {{ str($appointment->status)->replace('_', ' ')->title() }}</div>
                        <div style="font-size:14px;font-weight:700;color:#111827;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $appointment->patient?->name ?? 'Patient' }}</div>
                        <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:5px;">
                            @if($careStatus)
                                <span style="display:inline-flex;align-items:center;border-radius:9999px;padding:2px 7px;font-size:11px;font-weight:600;{{ $careStyle }}">Care Status: {{ $careStatus['label'] }}</span>
                            @endif
                            @if($language !== 'en')
                                <span style="display:inline-flex;align-items:center;border-radius:9999px;padding:2px 7px;font-size:11px;font-weight:600;background:#f3f4f6;color:#374151;">{{ $appointment->patient?->preferred_language_label }}</span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ $this->primaryActionUrl($appointment) }}" style="align-self:center;white-space:nowrap;font-size:12px;font-weight:700;color:#2563eb;text-decoration:none;">
                        {{ $this->primaryActionLabel($appointment) }}
                    </a>
                </div>
            @empty
                <p style="margin:0;padding:14px 0;color:#9ca3af;font-size:13px;">No appointments scheduled today.</p>
            @endforelse
        </div>
    </div>

    {{-- Calendar — no wrapper card; fills the page content area --}}
    <div id="practiq-appt-calendar" wire:ignore></div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script>
    (function () {
        var CREATE_BASE_URL = @json($createBaseUrl);
        var EVENTS_URL      = @json($eventsUrl);
        var CALENDAR_TZ     = @json($calendarTimezone);
        var INITIAL_DATE    = @json($initialDate);

        function initCalendar() {
            var el = document.getElementById('practiq-appt-calendar');
            if (!el || el._fc) return;

            function toCalendarWallTime(date) {
                var pad = function (n) { return String(n).padStart(2, '0'); };

                return date.getUTCFullYear() + '-'
                    + pad(date.getUTCMonth() + 1) + '-'
                    + pad(date.getUTCDate()) + ' '
                    + pad(date.getUTCHours()) + ':'
                    + pad(date.getUTCMinutes()) + ':'
                    + pad(date.getUTCSeconds());
            }

            function persistAppointmentTime(info) {
                @this.call(
                    'updateAppointmentTime',
                    info.event.id,
                    toCalendarWallTime(info.event.start),
                    info.event.end ? toCalendarWallTime(info.event.end) : null
                )
                    .then(function () {})
                    .catch(function () {
                        info.revert();
                    });
            }

            function badgeStyle(color) {
                switch (color) {
                    case 'success': return { background: '#d1fae5', color: '#065f46' };
                    case 'warning': return { background: '#fef3c7', color: '#92400e' };
                    case 'danger': return { background: '#fee2e2', color: '#991b1b' };
                    case 'info': return { background: '#e0f2fe', color: '#0c4a6e' };
                    case 'primary': return { background: '#dbeafe', color: '#1e40af' };
                    default: return { background: '#f3f4f6', color: '#374151' };
                }
            }

            function makeBadge(text, style) {
                var badge = document.createElement('span');
                badge.className = 'practiq-calendar-badge';
                badge.textContent = text;
                badge.style.background = style.background;
                badge.style.color = style.color;
                return badge;
            }

            var calendar = new FullCalendar.Calendar(el, {
                initialView: 'timeGridWeek',
                initialDate: INITIAL_DATE || undefined,
                timeZone: CALENDAR_TZ,
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
                    window.location.href = CREATE_BASE_URL
                        + (CREATE_BASE_URL.indexOf('?') === -1 ? '?' : '&')
                        + 'start_datetime=' + encodeURIComponent(formatted);
                },

                editable: true,

                eventContent: function (info) {
                    var props = info.event.extendedProps || {};
                    var root = document.createElement('div');
                    var title = document.createElement('div');
                    var badges = document.createElement('div');
                    var careStyle = badgeStyle(props.care_status_color);

                    root.className = 'practiq-calendar-event';
                    title.className = 'practiq-calendar-event-title';
                    title.textContent = info.timeText
                        ? info.timeText + ' ' + info.event.title
                        : info.event.title;

                    badges.className = 'practiq-calendar-badges';

                    if (props.care_status_label) {
                        badges.appendChild(makeBadge('Care Status: ' + props.care_status_label, careStyle));
                    }

                    if (props.preferred_language && props.preferred_language !== 'en' && props.preferred_language_label) {
                        badges.appendChild(makeBadge(props.preferred_language_label, {
                            background: '#f3f4f6',
                            color: '#374151'
                        }));
                    }

                    root.appendChild(title);
                    if (badges.children.length) {
                        root.appendChild(badges);
                    }

                    return { domNodes: [root] };
                },

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
                    var care   = info.event.extendedProps.care_status_label || '';
                    var language = info.event.extendedProps.preferred_language_label || '';
                    var parts  = [info.event.title];
                    if (type)   parts.push(type);
                    if (status) parts.push('(' + status.replace(/_/g, ' ') + ')');
                    if (care) parts.push('Care Status: ' + care);
                    if (language) parts.push(language);
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
