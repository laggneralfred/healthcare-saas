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

                {{-- Header --}}
                <tr>
                    <td style="background-color:#0D7377;padding:24px 32px;">
                        <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;letter-spacing:-0.5px;">Practiq</h1>
                        @if($messageLog->practice)
                            <p style="margin:4px 0 0 0;color:#a7d9db;font-size:13px;">{{ $messageLog->practice->name }}</p>
                        @endif
                    </td>
                </tr>

                {{-- Body --}}
                <tr>
                    <td style="padding:32px;">
                        <div style="color:#374151;font-size:15px;line-height:1.7;white-space:pre-line;">{{ $messageLog->body }}</div>
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="background-color:#f9fafb;border-top:1px solid #e5e7eb;padding:20px 32px;">
                        <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.5;">
                            To stop receiving reminders, reply with UNSUBSCRIBE or contact
                            {{ $messageLog->practice?->name ?? 'the practice' }} directly.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
