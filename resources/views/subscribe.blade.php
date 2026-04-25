<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Your Plan — Practiq</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
            color: #1a1a2e;
            line-height: 1.6;
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0D7377;
            margin-bottom: 1rem;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            color: #1a1a2e;
        }

        .header p {
            font-size: 1.1rem;
            color: #666;
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .pricing-card {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            position: relative;
        }

        .pricing-card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            transform: translateY(-4px);
        }

        .pricing-card.most-popular {
            border: 2px solid #0D7377;
            box-shadow: 0 8px 24px rgba(13, 115, 119, 0.2);
        }

        .pricing-card .badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: #0D7377;
            color: white;
            padding: 0.25rem 1rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .pricing-card h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #1a1a2e;
        }

        .pricing-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: #0D7377;
            margin-bottom: 0.25rem;
        }

        .pricing-price small {
            font-size: 1rem;
            color: #999;
        }

        .pricing-description {
            font-size: 0.85rem;
            color: #999;
            margin-bottom: 1.5rem;
        }

        .pricing-features {
            list-style: none;
            margin-bottom: 2rem;
        }

        .pricing-features li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #e5e5e5;
            font-size: 0.9rem;
            color: #555;
        }

        .pricing-features li:before {
            content: "✓ ";
            color: #10B981;
            font-weight: 700;
            margin-right: 0.5rem;
        }

        .btn {
            width: 100%;
            padding: 1rem;
            background: #0D7377;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            text-decoration: none;
            display: block;
            text-align: center;
            font-family: 'Inter', sans-serif;
        }

        .btn:hover {
            background: #055c69;
        }

        .trust-signals {
            text-align: center;
            margin-bottom: 3rem;
        }

        .trust-signals p {
            color: #999;
            font-size: 0.9rem;
            margin: 0.5rem 0;
        }

        .faq {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .faq h3 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #1a1a2e;
        }

        .faq-item {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e5e5e5;
        }

        .faq-item:last-child {
            border-bottom: none;
        }

        .faq-item strong {
            display: block;
            color: #0D7377;
            margin-bottom: 0.5rem;
        }

        .faq-item p {
            color: #666;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.75rem;
            }

            .pricing-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Practiq</div>
            <h1>Your trial has expired</h1>
            <p>Choose a plan to continue using Practiq</p>
        </div>

        <div class="trust-signals">
            <p>✓ Cancel anytime</p>
            <p>✓ No setup fees</p>
            <p>✓ All features included</p>
        </div>

        <div class="pricing-grid">
            <!-- Solo Plan -->
            <div class="pricing-card">
                <h3>Solo</h3>
                <div class="pricing-price">$49<small>/month</small></div>
                <p class="pricing-description">1 practitioner</p>
                <ul class="pricing-features">
                    <li>Online booking wizard</li>
                    <li>Patient intake forms</li>
                    <li>Clinical visit notes</li>
                    <li>Checkout & payments</li>
                    <li>Email support</li>
                </ul>
                <a href="{{ route('filament.admin.pages.billing') }}" class="btn">Choose Plan</a>
            </div>

            <!-- Clinic Plan (Most Popular) -->
            <div class="pricing-card most-popular">
                <span class="badge">Most Popular</span>
                <h3>Clinic</h3>
                <div class="pricing-price">$99<small>/month</small></div>
                <p class="pricing-description">Up to 5 practitioners</p>
                <ul class="pricing-features">
                    <li>Everything in Solo</li>
                    <li>Multi-practitioner scheduling</li>
                    <li>Practice dashboard & metrics</li>
                    <li>Priority support</li>
                </ul>
                <a href="{{ route('filament.admin.pages.billing') }}" class="btn">Choose Plan</a>
            </div>

            <!-- Enterprise Plan -->
            <div class="pricing-card">
                <h3>Enterprise</h3>
                <div class="pricing-price">$199<small>/month</small></div>
                <p class="pricing-description">Unlimited practitioners</p>
                <ul class="pricing-features">
                    <li>Everything in Clinic</li>
                    <li>Custom onboarding</li>
                    <li>Dedicated support</li>
                    <li>API access</li>
                </ul>
                <a href="{{ route('filament.admin.pages.billing') }}" class="btn">Choose Plan</a>
            </div>
        </div>

        @auth
        <div style="background-color: #fef3c7; border-radius: 8px; padding: 2rem; margin-bottom: 3rem; border-left: 4px solid #f59e0b;">
            <h3 style="color: #92400e; font-size: 1.25rem; margin-bottom: 1rem;">Before you decide — download your data</h3>
            <p style="color: #b45309; margin-bottom: 1.5rem;">
                Your practice data is retained for 30 days after your trial expires. Download a complete backup now to ensure you have a copy of all your information.
            </p>
            <form method="POST" action="{{ route('export.request') }}" style="display: inline-block;">
                @csrf
                <input type="hidden" name="format" value="csv">
                <button type="submit" style="background: #f59e0b; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.3s;">
                    Download My Data (CSV)
                </button>
            </form>
            <span style="color: #92400e; margin: 0 0.75rem;">or</span>
            <form method="POST" action="{{ route('export.request') }}" style="display: inline-block;">
                @csrf
                <input type="hidden" name="format" value="json">
                <button type="submit" style="background: #92400e; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.3s;">
                    Download My Data (JSON)
                </button>
            </form>
        </div>
        @endauth

        <div class="faq">
            <h3>Questions?</h3>
            <div class="faq-item">
                <strong>What happens to my data?</strong>
                <p>Your practice, patients, and all data remain safe. Once you subscribe, you'll have full access to your dashboard again.</p>
            </div>
            <div class="faq-item">
                <strong>Can I change my plan later?</strong>
                <p>Yes. You can upgrade, downgrade, or change your plan anytime from your dashboard. Changes take effect on your next billing cycle.</p>
            </div>
            <div class="faq-item">
                <strong>Do you offer discounts for annual billing?</strong>
                <p>Contact our team to discuss annual pricing options. We're happy to work with you.</p>
            </div>
            <div class="faq-item">
                <strong>Need help?</strong>
                <p>Email us at support@practiqapp.com or reply to any previous email. Our team typically responds within 24 hours.</p>
            </div>
        </div>
    </div>
</body>
</html>
