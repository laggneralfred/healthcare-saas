<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New patient forms</title>
</head>
<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background-color:#f4f4f4;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f4f4;">
    <tr>
        <td align="center" style="padding:20px 0;">
            <table role="presentation" width="600" cellspacing="0" cellpadding="0"
                   style="max-width:600px;background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                <tr>
                    <td style="background-color:#0D7377;padding:24px 32px;">
                        <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;">{{ $interest->practice?->name ?? 'Practiq' }}</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px;">
                        <p style="margin:0 0 16px;color:#374151;font-size:15px;line-height:1.7;">
                            Hi {{ $interest->first_name }},
                        </p>
                        <p style="margin:0 0 16px;color:#374151;font-size:15px;line-height:1.7;">
                            {{ $interest->practice?->name ?? 'The clinic' }} has sent you a short form to help staff review your request.
                        </p>
                        <div style="margin-top:24px;">
                            <a href="{{ $formUrl }}" style="display:inline-block;background:#0D7377;color:#ffffff;text-decoration:none;border-radius:6px;padding:12px 18px;font-size:14px;font-weight:700;">
                                Complete intake form
                            </a>
                        </div>
                        <p style="margin:12px 0 0;color:#6b7280;font-size:13px;line-height:1.5;">
                            This secure link is only for these forms and does not create a patient portal account. The link expires in 7 days.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
