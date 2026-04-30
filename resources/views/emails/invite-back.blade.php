<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $messageLog->subject }}</title>
</head>
<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background-color:#f4f4f4;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f4f4;">
    <tr>
        <td align="center" style="padding:20px 0;">
            <table role="presentation" width="600" cellspacing="0" cellpadding="0"
                   style="max-width:600px;background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);">

                <tr>
                    <td style="background-color:#0D7377;padding:24px 32px;">
                        <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;">{{ $messageLog->practice?->name ?? 'Practiq' }}</h1>
                    </td>
                </tr>

                <tr>
                    <td style="padding:32px;">
                        <div style="color:#374151;font-size:15px;line-height:1.7;white-space:pre-line;">{{ $messageLog->body }}</div>
                        @if($requestUrl)
                            <div style="margin-top:24px;">
                                <a href="{{ $requestUrl }}" style="display:inline-block;background:#0D7377;color:#ffffff;text-decoration:none;border-radius:6px;padding:12px 18px;font-size:14px;font-weight:700;">
                                    Request an appointment
                                </a>
                            </div>
                            <p style="margin:12px 0 0;color:#6b7280;font-size:13px;line-height:1.5;">
                                This sends a request to the practice only. Staff will contact you to schedule and confirm an appointment.
                            </p>
                        @endif
                    </td>
                </tr>

                <tr>
                    <td style="background-color:#f9fafb;border-top:1px solid #e5e7eb;padding:20px 32px;">
                        <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.5;">
                            To stop receiving messages, contact {{ $messageLog->practice?->name ?? 'the practice' }} directly.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
