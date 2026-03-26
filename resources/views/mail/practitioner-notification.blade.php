<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:#1e293b;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;padding:2rem 1rem;">
  <tr>
    <td>
      <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:0.75rem;overflow:hidden;border:1px solid #e2e8f0;">

        <tr>
          <td style="background:#1e40af;padding:1.5rem 2rem;">
            <p style="margin:0;color:#ffffff;font-size:1.125rem;font-weight:700;">New Appointment Booked</p>
            <p style="margin:0.25rem 0 0;color:#bfdbfe;font-size:0.875rem;">{{ $appointment->practice->name }}</p>
          </td>
        </tr>

        <tr>
          <td style="padding:2rem;">
            <p style="margin:0 0 1rem;font-size:1rem;color:#1e293b;">
              Hi {{ $appointment->practitioner->user->name }},
            </p>
            <p style="margin:0 0 1.5rem;color:#475569;font-size:0.9375rem;">
              A new appointment has been booked with you:
            </p>

            <table width="100%" cellpadding="4" cellspacing="0" style="background:#f1f5f9;border-radius:0.5rem;padding:1.25rem;margin-bottom:1.5rem;">
              <tr>
                <td style="color:#64748b;font-size:0.875rem;width:40%;">Patient</td>
                <td style="color:#0f172a;font-size:0.875rem;font-weight:600;">{{ $appointment->patient->name }}</td>
              </tr>
              <tr>
                <td style="color:#64748b;font-size:0.875rem;">Email</td>
                <td style="color:#0f172a;font-size:0.875rem;">{{ $appointment->patient->email }}</td>
              </tr>
              @if($appointment->patient->phone)
              <tr>
                <td style="color:#64748b;font-size:0.875rem;">Phone</td>
                <td style="color:#0f172a;font-size:0.875rem;">{{ $appointment->patient->phone }}</td>
              </tr>
              @endif
              <tr>
                <td style="color:#64748b;font-size:0.875rem;">Date &amp; Time</td>
                <td style="color:#0f172a;font-size:0.875rem;font-weight:600;">
                  {{ $appointment->start_datetime->setTimezone($appointment->practice->timezone ?? 'UTC')->format('l, F j, Y \a\t g:i A') }}
                </td>
              </tr>
              <tr>
                <td style="color:#64748b;font-size:0.875rem;">Type</td>
                <td style="color:#0f172a;font-size:0.875rem;">{{ $appointment->appointmentType->name }}</td>
              </tr>
            </table>

            <p style="margin:0;color:#94a3b8;font-size:0.8125rem;">
              Log in to the admin panel to view the full appointment details.
            </p>
          </td>
        </tr>

        <tr>
          <td style="background:#f8fafc;padding:1rem 2rem;border-top:1px solid #e2e8f0;">
            <p style="margin:0;color:#94a3b8;font-size:0.75rem;text-align:center;">{{ $appointment->practice->name }}</p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
