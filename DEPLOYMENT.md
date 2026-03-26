# Healthcare SaaS — Production Deployment Guide

> Last updated: 2026-03-26. Reflects Sprint 22 feature freeze.

## What's Included

### Core Features
- **Multi-tenant admin panel** (Filament v5) — each practice is fully isolated via `practice_id`
- **Public booking page** — `/book/{practice:slug}` — no login required; 5-step availability calendar wizard
- **Patient intake & consent forms** — token-based public forms with cross-linking after submission
- **Encounter notes** — acupuncture-specialised visit recording (extensible to other disciplines)
- **Checkout & payment tracking** — state-machine checkout sessions (open → paid / payment_due)
- **Stripe subscription billing** — Solo / Clinic / Enterprise plans via Laravel Cashier

### Admin Panel Structure
- Dashboard at `/admin/dashboard` — monthly metrics, status breakdown, revenue by practitioner
- Practice switcher in top bar — super-admins (no `practice_id`) can switch between all practices; regular users see their own practice name
- All resource tables are scoped to the currently selected practice via `BelongsToPractice` trait
- Subscription middleware bypassed in `local` environment; enforced in production for practice users
- Billing page at `/admin/billing` — Stripe Checkout + swap + billing portal

### Email Notifications (queued)
- **Patient confirmation** — appointment details + intake and consent CTA buttons
- **Practitioner notification** — new booking summary with patient contact info

---

## Pre-Deployment Checklist

### Environment & Configuration
- [ ] Create `.env` from `.env.production.example`
- [ ] Generate `APP_KEY` via `php artisan key:generate`
- [ ] Set `APP_DEBUG=false` in production
- [ ] Configure database (PostgreSQL recommended)
- [ ] Configure Redis for caching and sessions
- [ ] Set `SESSION_SECURE_COOKIES=true` and `SESSION_ENCRYPT=true`

### Database
- [ ] Create PostgreSQL database
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Seed subscription plans: `php artisan db:seed --class=DatabaseSeeder --force`
- [ ] (Optional) Seed demo data: `php artisan db:seed --class=DemoSeeder --force`
- [ ] Verify database backups are configured

### Queue Worker (required for email notifications)
- [ ] Configure queue driver (`QUEUE_CONNECTION=database` or `redis`)
- [ ] Start queue worker: `php artisan queue:work --daemon`
- [ ] Or configure Laravel Horizon / Supervisor for production
- [ ] Test email dispatch by booking a test appointment via `/book/{slug}`

### File Storage
- [ ] Configure AWS S3 or compatible storage
- [ ] Set `FILESYSTEM_DISK=s3` in `.env`
- [ ] Create bucket with proper CORS configuration
- [ ] Configure bucket encryption and versioning

### Email & Communication
- [ ] Set up Postmark API account for transactional emails
- [ ] Update `POSTMARK_API_KEY` in `.env`
- [ ] Test email delivery with sample email
- [ ] Configure sender domain with SPF/DKIM records

### Stripe Integration
- [ ] Switch to live Stripe keys (`pk_live_*` and `sk_live_*`)
- [ ] Register webhook endpoint: `https://your-domain.com/stripe/webhook`
- [ ] Generate webhook signing secret and set `STRIPE_WEBHOOK_SECRET`
- [ ] Create live subscription plan Price IDs in Stripe Dashboard
- [ ] Update `STRIPE_SOLO_PRICE`, `STRIPE_CLINIC_PRICE`, `STRIPE_ENTERPRISE_PRICE` in `.env`
- [ ] Update `SubscriptionPlan` rows via `stripe_price_id` column (seeder reads from config)
- [ ] Run `php artisan stripe:sync --practice-id=X` after first subscription created to confirm local DB sync
- [ ] Test payment flow end-to-end: subscribe → webhook → admin shows "Current Plan"
- [ ] Verify fake/test Stripe IDs are NOT in database (`practices.stripe_id` should be null before first payment)

### Security
- [ ] Enable HTTPS (SSL/TLS certificate required)
- [ ] Configure CORS for allowed domains
- [ ] Set security headers (HSTS, CSP, etc.)
- [ ] Disable debug toolbar in production
- [ ] Review and update `.gitignore` to exclude sensitive files
- [ ] Configure rate limiting for API endpoints
- [ ] Set up API authentication tokens for practitioner/patient APIs

### Logging & Monitoring
- [ ] Configure centralized logging (e.g., Sentry, LogRocket)
- [ ] Set `LOG_LEVEL=notice` or `warning`
- [ ] Set up error tracking with Sentry integration
- [ ] Configure health check endpoint
- [ ] Set up application performance monitoring (APM)
- [ ] Test log rotation and retention policies

### Caching & Performance
- [ ] Configure Redis connection in `.env`
- [ ] Cache configuration: `php artisan config:cache`
- [ ] Cache routes: `php artisan route:cache`
- [ ] Optimize autoloader: `composer install --optimize-autoloader`
- [ ] Configure Vite for production: `npm run build`

### CDN & Assets
- [ ] Configure CDN for static assets
- [ ] Verify Vite build outputs to `public/build`
- [ ] Set up cache busting for assets
- [ ] Test asset delivery with CloudFlare or similar

### Backup & Disaster Recovery
- [ ] Configure automated database backups
- [ ] Test database restore procedures
- [ ] Configure S3 bucket versioning and backups
- [ ] Document disaster recovery plan
- [ ] Set up uptime monitoring (UptimeRobot, Pingdom)

### Access Control & Multi-Tenancy
- [ ] Verify practice isolation via `practice_id` scoping (`BelongsToPractice` trait on all resources)
- [ ] Confirm `RequiresActiveSubscription` middleware is active (`APP_ENV=production`, not `local`)
- [ ] Test regular user cannot see other practices' data
- [ ] Test super-admin (no `practice_id`) can switch between practices via top-bar switcher
- [ ] Verify public booking page `/book/{slug}` is accessible without login
- [ ] Verify intake/consent token URLs are not guessable (64-char random token)

---

## Deployment Steps

### 1. Prepare Application

```bash
# Install dependencies
composer install --optimize-autoloader --no-dev

# Build frontend assets
npm install && npm run build

# Clear cached configuration
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Cache configuration for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. Run Migrations & Seed

```bash
# Run all pending migrations
php artisan migrate --force

# Seed subscription plan catalog (idempotent — safe to re-run)
php artisan db:seed --class=DatabaseSeeder --force

# Optional: seed demo practice with sample data
php artisan db:seed --class=DemoSeeder --force
# Login: demo@example.com / password
# Booking: /book/demo-acupuncture-clinic
```

### 3. Set Permissions

```bash
# Set proper file permissions (adjust paths as needed)
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage/logs
chown -R www-data:www-data storage bootstrap/cache
```

### 4. Configure Web Server

#### Nginx Configuration
```nginx
server {
    listen 443 ssl http2;
    server_name your-domain.com;

    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;

    # Redirect HTTP to HTTPS
    if ($scheme != "https") {
        return 301 https://$server_name$request_uri;
    }

    root /path/to/healthcare-saas/public;
    index index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # HSTS (uncomment after verifying HTTPS works)
    # add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~* ^/(?:storage|bootstrap)/ {
        deny all;
    }
}
```

#### Apache Configuration (.htaccess)
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# Security headers
<IfModule mod_headers.c>
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
</IfModule>
```

### 5. Configure Queue Workers (optional)

If using job queues for background tasks:

```bash
# Start queue worker (supervisor recommended for production)
php artisan queue:work database --timeout=3600
```

### 6. Health Checks

```bash
# Verify application is running
curl https://your-domain.com

# Check database connection
php artisan tinker --execute="DB::connection()->getPdo();"

# Test Stripe configuration
php artisan tinker --execute="echo config('services.stripe.secret_key')"

# Verify caching
php artisan tinker --execute="echo Cache::get('test')"
```

---

## Post-Deployment

### Monitoring & Maintenance
1. Monitor application logs for errors
2. Check Stripe webhook deliveries
3. Verify email delivery
4. Monitor database performance
5. Review uptime monitoring alerts

### Regular Maintenance
- [ ] Daily: Review error logs
- [ ] Weekly: Database backups verification, security updates
- [ ] Monthly: Performance metrics review, dependency updates
- [ ] Quarterly: Security audit, disaster recovery test

---

## Rollback Procedure

If deployment fails:

```bash
# Revert database migrations
php artisan migrate:rollback --force

# Restore previous application code from version control
git checkout previous-tag

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## Support

For issues during deployment:
- Check application logs: `storage/logs/laravel.log`
- Verify `.env` configuration
- Ensure all required services are running (DB, Redis, etc.)
- Review Stripe webhook logs in Dashboard
- Contact support with error messages and logs
