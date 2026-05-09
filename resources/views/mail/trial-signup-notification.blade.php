<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Practiq trial signup</title>
</head>
<body style="font-family: Arial, sans-serif; color: #0f172a; line-height: 1.5;">
    <h1 style="font-size: 20px; margin: 0 0 16px;">New Practiq trial signup</h1>

    <table cellpadding="0" cellspacing="0" style="border-collapse: collapse; width: 100%; max-width: 640px;">
        <tbody>
            <tr>
                <td style="padding: 8px 0; font-weight: 700; width: 180px;">Name</td>
                <td style="padding: 8px 0;">{{ $trialSignup->name }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: 700;">Email</td>
                <td style="padding: 8px 0;">{{ $trialSignup->email }}</td>
            </tr>
            @if($trialSignup->phone)
                <tr>
                    <td style="padding: 8px 0; font-weight: 700;">Phone</td>
                    <td style="padding: 8px 0;">{{ $trialSignup->phone }}</td>
                </tr>
            @endif
            <tr>
                <td style="padding: 8px 0; font-weight: 700;">Practice</td>
                <td style="padding: 8px 0;">{{ $trialSignup->practice_name ?: 'Not provided' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: 700;">Profession / Practice type</td>
                <td style="padding: 8px 0;">{{ $trialSignup->profession ?: $trialSignup->practice_type ?: 'Not provided' }}</td>
            </tr>
            @if($trialSignup->heard_about_us)
                <tr>
                    <td style="padding: 8px 0; font-weight: 700;">Heard about us</td>
                    <td style="padding: 8px 0;">{{ $trialSignup->heard_about_us }}</td>
                </tr>
            @endif
            <tr>
                <td style="padding: 8px 0; font-weight: 700;">Signed up</td>
                <td style="padding: 8px 0;">{{ $trialSignup->signed_up_at?->format('M j, Y g:i A T') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
