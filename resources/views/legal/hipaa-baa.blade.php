<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HIPAA / BAA Acknowledgement — Practiq</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background:#f9fafb; color:#1f2937; line-height:1.6; margin:0; }
        .container { max-width: 800px; margin: 0 auto; padding: 40px 20px; }
        .content { background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:32px; }
        h1 { color:#0D7377; margin:0 0 8px; }
        h2 { color:#0D7377; margin-top:28px; }
        a { color:#0D7377; font-weight:600; text-decoration:none; }
        a:hover { text-decoration:underline; }
        .updated { color:#6b7280; font-size:14px; margin:0 0 24px; }
    </style>
</head>
<body>
    <main class="container">
        <div class="content">
            <h1>HIPAA / BAA Acknowledgement</h1>
            <p class="updated">Version {{ config('legal.documents.hipaa_baa_acknowledgement.version') }}</p>

            <p>Practiq is intended to help practices manage patient and clinical information, including scheduling, intake forms, visit notes, follow-up, and related practice operations.</p>

            <p>Practices are responsible for using Practiq according to applicable privacy, security, professional, and healthcare laws. These responsibilities can vary by location, license type, and the services your practice provides.</p>

            <p>If your practice stores protected health information in Practiq, a Business Associate Agreement may be required. The HIPAA / BAA acknowledgement is part of setup readiness so practice owners and administrators review this responsibility before entering real patient or clinical data.</p>

            <p>This page is not legal advice. Practices should consult qualified counsel or compliance advisors for their own requirements.</p>

            <p><a href="{{ route('terms') }}">Terms of Service</a> · <a href="{{ route('privacy') }}">Privacy Policy</a></p>
        </div>
    </main>
</body>
</html>
