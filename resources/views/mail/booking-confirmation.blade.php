<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:#1e293b;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;padding:2rem 1rem;">
  <tr>
    <td>
      <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:0.75rem;overflow:hidden;border:1px solid #e2e8f0;">

        {{-- Header --}}
        <tr>
          <td style="background:#0d9488;padding:1.5rem 2rem;">
            <p style="margin:0;color:#ffffff;font-size:1.25rem;font-weight:700;">
              {{ $appointment->practice->name }}
            </p>
            <p style="margin:0.25rem 0 0;color:#ccfbf1;font-size:0.875rem;">Appointment Confirmed</p>
          </td>
        </tr>

        {{-- Body --}}
        <tr>
          <td style="padding:2rem;">

            <p style="margin:0 0 1rem;font-size:1rem;color:#1e293b;">
              Hi {{ $appointment->patient->name }},
            </p>
            <p style="margin:0 0 1.5rem;color:#475569;font-size:0.9375rem;line-height:1.6;">
              Your appointment has been confirmed. Here are the details:
            </p>

            {{-- Appointment Details Card --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;border-radius:0.5rem;padding:1.25rem;margin-bottom:1.5rem;">
              <tr>
                <td>
                  <table width="100%" cellpadding="4" cellspacing="0">
                    <tr>
                      <td style="color:#64748b;font-size:0.8125rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;padding-bottom:0.25rem;" colspan="2">Appointment Details</td>
                    </tr>
                    <tr>
                      <td style="color:#64748b;font-size:0.875rem;width:40%;">Date &amp; Time</td>
                      <td style="color:#0f172a;font-size:0.875rem;font-weight:600;">
                        {{ $appointment->start_datetime->setTimezone($appointment->practice->timezone ?? 'UTC')->format('l, F j, Y \a\t g:i A') }}
                      </td>
                    </tr>
                    <tr>
                      <td style="color:#64748b;font-size:0.875rem;">Type</td>
                      <td style="color:#0f172a;font-size:0.875rem;">{{ $appointment->appointmentType->name }}</td>
                    </tr>
                    <tr>
                      <td style="color:#64748b;font-size:0.875rem;">Practitioner</td>
                      <td style="color:#0f172a;font-size:0.875rem;">{{ $appointment->practitioner->user->name }}</td>
                    </tr>
                    <tr>
                      <td style="color:#64748b;font-size:0.875rem;">Practice</td>
                      <td style="color:#0f172a;font-size:0.875rem;">{{ $appointment->practice->name }}</td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            {{-- Action Steps --}}
            <p style="margin:0 0 0.75rem;font-size:0.9375rem;font-weight:600;color:#0f172a;">
              Before your appointment, please complete:
            </p>

            {{-- Intake Button --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:0.75rem;">
              <tr>
                <td>
                  <a href="{{ $intake->getPublicUrl() }}"
                     style="display:block;background:#0d9488;color:#ffffff;text-decoration:none;padding:0.875rem 1.25rem;border-radius:0.5rem;font-weight:600;font-size:0.9375rem;">
                    📋 Complete Intake Form
                  </a>
                </td>
              </tr>
            </table>

            {{-- Consent Button --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:1.5rem;">
              <tr>
                <td>
                  <a href="{{ $consent->getPublicUrl() }}"
                     style="display:block;background:#0f766e;color:#ffffff;text-decoration:none;padding:0.875rem 1.25rem;border-radius:0.5rem;font-weight:600;font-size:0.9375rem;">
                    ✍️ Sign Consent Form
                  </a>
                </td>
              </tr>
            </table>

            <p style="margin:0;color:#94a3b8;font-size:0.8125rem;line-height:1.5;">
              If you need to reschedule or cancel, please contact us directly.<br>
              We look forward to seeing you!
            </p>

          </td>
        </tr>

        {{-- Footer --}}
        <tr>
          <td style="background:#f8fafc;padding:1rem 2rem;border-top:1px solid #e2e8f0;">
            <p style="margin:0;color:#94a3b8;font-size:0.75rem;text-align:center;">
              {{ $appointment->practice->name }}
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
