<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Practiq</title>
</head>
<body style="font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; line-height: 1.6; color: #333;">

<table style="width: 100%; background-color: #f5f5f5; padding: 20px 0;">
    <tr>
        <td style="text-align: center; padding: 20px;">
            <table style="max-width: 600px; width: 100%; background-color: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin: 0 auto;">
                <!-- Header -->
                <tr style="background: linear-gradient(135deg, #0D7377 0%, #055c69 100%); color: white;">
                    <td style="padding: 40px 30px; text-align: center;">
                        <h1 style="margin: 0; font-size: 32px; font-weight: 700;">Practiq</h1>
                        <p style="margin: 8px 0 0 0; font-size: 14px; opacity: 0.9;">Practice management for practitioners</p>
                    </td>
                </tr>

                <!-- Content -->
                <tr>
                    <td style="padding: 40px 30px;">
                        <h2 style="margin: 0 0 20px 0; font-size: 24px; color: #1a1a2e;">Welcome, {{ $user->name }}!</h2>

                        <p style="margin: 0 0 20px 0; color: #555;">
                            Your <strong>{{ $practice->name }}</strong> practice on Practiq is ready to go.
                        </p>

                        <p style="margin: 0 0 20px 0; color: #555;">
                            You have a full <strong>30 days</strong> to explore every feature with no credit card required. Your trial expires on <strong>{{ $practice->trial_ends_at->format('F j, Y') }}</strong>.
                        </p>

                        <p style="margin: 0 0 30px 0; color: #555; font-weight: 500; font-size: 16px;">
                            What you can do right now:
                        </p>

                        <!-- Feature List -->
                        <ul style="margin: 0 0 30px 0; padding-left: 0; list-style: none;">
                            <li style="margin: 0 0 12px 0; color: #555; display: flex; align-items: flex-start;">
                                <span style="color: #0D7377; font-weight: bold; margin-right: 10px; flex-shrink: 0;">✓</span>
                                <span>Set up your online booking page for patients</span>
                            </li>
                            <li style="margin: 0 0 12px 0; color: #555; display: flex; align-items: flex-start;">
                                <span style="color: #0D7377; font-weight: bold; margin-right: 10px; flex-shrink: 0;">✓</span>
                                <span>Create digital intake and consent forms</span>
                            </li>
                            <li style="margin: 0 0 12px 0; color: #555; display: flex; align-items: flex-start;">
                                <span style="color: #0D7377; font-weight: bold; margin-right: 10px; flex-shrink: 0;">✓</span>
                                <span>Document clinical encounter notes</span>
                            </li>
                            <li style="margin: 0 0 20px 0; color: #555; display: flex; align-items: flex-start;">
                                <span style="color: #0D7377; font-weight: bold; margin-right: 10px; flex-shrink: 0;">✓</span>
                                <span>Process payments with built-in Stripe integration</span>
                            </li>
                        </ul>

                        <!-- CTA Button -->
                        <table style="margin: 30px 0; width: 100%;">
                            <tr>
                                <td style="text-align: center;">
                                    <a href="https://app.practiqapp.com/admin" style="display: inline-block; padding: 14px 32px; background-color: #0D7377; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">
                                        Go to Your Dashboard
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin: 30px 0 0 0; padding-top: 20px; border-top: 1px solid #e5e5e5; color: #999; font-size: 14px;">
                            <strong>Questions?</strong> Reply to this email or visit our documentation. We're here to help.
                        </p>
                    </td>
                </tr>

                <!-- Footer -->
                <tr style="background-color: #f9f9f9; border-top: 1px solid #e5e5e5;">
                    <td style="padding: 20px 30px; text-align: center; color: #999; font-size: 12px;">
                        <p style="margin: 0;">
                            © 2026 Practiq. All rights reserved.<br>
                            <a href="https://practiqapp.com" style="color: #0D7377; text-decoration: none;">Visit our website</a>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>
