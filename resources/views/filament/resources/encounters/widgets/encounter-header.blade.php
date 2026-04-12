<div style="padding: 1.5rem; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-bottom: 1.5rem;">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <div>
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.5rem;">
                <span style="font-size: 0.875rem; color: #6b7280;">Patient</span>
            </div>
            <div style="font-size: 1rem; font-weight: 500; color: #1f2937;">{{ $patientName }}</div>
        </div>

        <div>
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.5rem;">
                <span style="font-size: 0.875rem; color: #6b7280;">Practitioner</span>
            </div>
            <div style="font-size: 1rem; font-weight: 500; color: #1f2937;">{{ $practitionerName }}</div>
        </div>

        <div>
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.5rem;">
                <span style="font-size: 0.875rem; color: #6b7280;">Date</span>
            </div>
            <div style="font-size: 1rem; font-weight: 500; color: #1f2937;">{{ $visitDate }}</div>
        </div>

        <div>
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.5rem;">
                <span style="font-size: 0.875rem; color: #6b7280;">Discipline</span>
            </div>
            <div style="font-size: 1rem; font-weight: 500; color: #1f2937;">{{ $discipline }}</div>
        </div>

        <div>
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.5rem;">
                <span style="font-size: 0.875rem; color: #6b7280;">Appointment</span>
            </div>
            <div style="font-size: 1rem; font-weight: 500; color: #1f2937;">{{ $appointmentTime }}</div>
        </div>

        <div>
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.5rem;">
                <span style="font-size: 0.875rem; color: #6b7280;">Status</span>
            </div>
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.25rem 0.75rem; border-radius: 9999px;
                @switch($statusColor)
                    @case('success')
                        background-color: #d1fae5; color: #065f46;
                    @break
                    @default
                        background-color: #f3f4f6; color: #374151;
                @endswitch
            ">
                <span style="width: 0.5rem; height: 0.5rem; border-radius: 50%; background-color: currentColor;"></span>
                <span style="font-size: 0.875rem; font-weight: 500;">{{ ucfirst($status) }}</span>
            </div>
        </div>
    </div>
</div>
