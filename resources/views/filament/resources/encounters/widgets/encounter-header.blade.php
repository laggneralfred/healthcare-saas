<div style="padding: 0.75rem 1rem; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-bottom: 1rem;">
    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem 1.5rem; align-items: center;">
        <div style="min-width: 12rem;">
            <span style="font-size: 0.6875rem; line-height: 1rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.04em;">Patient</span>
            <span style="display: block; font-size: 1.05rem; line-height: 1.5rem; font-weight: 700; color: #111827;">{{ $patientName }}</span>
        </div>

        <div>
            <span style="font-size: 0.6875rem; line-height: 1rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.04em;">Date</span>
            <span style="display: block; font-size: 0.875rem; line-height: 1.25rem; color: #374151;">{{ $visitDate }}</span>
        </div>

        <div>
            <span style="font-size: 0.6875rem; line-height: 1rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.04em;">Practitioner</span>
            <span style="display: block; font-size: 0.875rem; line-height: 1.25rem; color: #374151;">{{ $practitionerName }}</span>
        </div>

        <div>
            <span style="font-size: 0.6875rem; line-height: 1rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.04em;">Discipline</span>
            <span style="display: block; font-size: 0.875rem; line-height: 1.25rem; color: #374151;">{{ $discipline }}</span>
        </div>

        <div>
            <span style="font-size: 0.6875rem; line-height: 1rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.04em;">Appointment</span>
            <span style="display: block; font-size: 0.875rem; line-height: 1.25rem; color: #374151;">{{ $appointmentTime }}</span>
        </div>

        <div style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.25rem 0.625rem; border-radius: 9999px;
            @switch($statusColor)
                @case('success')
                    background-color: #d1fae5; color: #065f46;
                @break
                @default
                    background-color: #f3f4f6; color: #374151;
            @endswitch
        ">
            <span style="width: 0.5rem; height: 0.5rem; border-radius: 50%; background-color: currentColor;"></span>
            <span style="font-size: 0.8125rem; font-weight: 500;">{{ ucfirst($status) }}</span>
        </div>
    </div>
</div>
