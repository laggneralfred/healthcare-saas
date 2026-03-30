# Practiq — Healthcare SaaS Platform

## Summary

**Practiq** is a multi-tenant practice management SaaS platform for solo health practitioners (acupuncturists, massage therapists, chiropractors, physiotherapists). Built by Alfred, a retired health practitioner, it competes with Jane App and SimplePractice.

**Current Status (March 2026):** MVP launched with 30-day free trials, online booking, patient intake/consent forms, clinical encounter documentation, Stripe payment processing, subscription billing (3 tiers), CSV patient importer, **and comprehensive data export** (CSV ZIP and JSON). 100+ tests passing. Live at app.practiqapp.com.

**Core Stack:** Laravel 13 + Filament v5 admin UI + Livewire v3 reactive components + PostgreSQL + Docker (Debian) + DigitalOcean. Production deployed via GitHub Actions CI/CD.

**Architecture:** Single-database multi-tenancy with application-level `practice_id` scoping via `BelongsToPractice` trait on all models. No RLS. State machine pattern for appointments and checkouts. Queued jobs for CSV imports and low-stock alerts.

**Key Rules:** Never break multi-tenancy (all queries are scoped). Never use Alpine in Docker (causes 75-minute builds). Never call `parent::boot()` in AdminPanelProvider (Filament has no boot method). Never mount `.:/app` in Docker volumes (overwrites vendor). Never use `.nullable()` on Filament TextColumn (causes hidden errors — use `.placeholder()` instead).

**Quick Start:** `php artisan serve` → http://localhost:8000/admin | Email: `admin@healthcare.test` | Password: `password` | See CLAUDE.md for full dev/deployment workflow.

---

## Project Overview

**Product:** Practiq (practiqapp.com) — multi-tenant healthcare SaaS for solo health practitioners
**Solo Developer:** Alfred, retired health practitioner, ELF Consulting
**Live:** app.practiqapp.com (production)
**Competitors:** Jane App, SimplePractice
**Stage:** MVP launched with trial registration, core features, and subscription billing

---

## Tech Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| **Framework** | Laravel | 13.x |
| **Admin UI** | Filament | 5.x |
| **Reactive UI** | Livewire | 3.x |
| **Database** | PostgreSQL | Latest (dev + prod) |
| **CSS** | Tailwind CSS | 4.x (via Vite) |
| **State Machine** | spatie/laravel-model-states | 2.12.x |
| **Payments** | Laravel Cashier + Stripe | Latest |
| **Container** | Docker + Caddy | Latest |
| **Cloud** | DigitalOcean | 137.184.33.220 |
| **Dev Environment** | WSL Ubuntu 22.04 | - |

---

## Subscription Tiers

| Plan | Price | Practitioners | Features |
|------|-------|---|---|
| **Solo** | $49/month | 1 | Scheduling, intake, encounter notes, checkout |
| **Clinic** | $99/month | Up to 5 | All Solo + multi-practitioner, team reporting |
| **Enterprise** | $199/month | Unlimited | All features, priority support, integrations |
| **Herb & Product Inventory** (add-on) | $19/month | All tiers | Low-stock alerts, inventory tracking |

---

## Architecture Rules (Never Break These)

### Multi-tenancy & Scoping
- **All models must use `BelongsToPractice` trait** — enforces `practice_id` global scope
- `bootBelongsToPractice()` uses table-qualified columns: `practice_id = auth()->user()->practice_id`
- Null guards and `withoutPracticeScope()` escape hatch for admin/system queries
- PostgreSQL RLS intentionally disabled — all scoping is application-level
- Composite indexes on `practice_id + filter_column` for query performance

### Filament v5 Specifics
- **Never use `.nullable()` on TextColumn** — causes hidden errors. Use `.placeholder('—')` instead.
- **Render hooks must be registered in `boot()` NOT `register()`** — Filament kernel must be initialized.
- **PanelProvider has NO `parent::boot()` to call** — it doesn't have a boot() method.
- **Do not call `parent::boot()` in AdminPanelProvider** — causes fatal error.
- Tailwind does not compile for Filament — use inline `style=""` attributes instead.
- Use `public` Livewire properties, NOT `getViewData()` for reactive state.
- `Blade::render()` in render hooks does not register Livewire components.
- Run `php artisan optimize:clear` when Filament routes go missing.

### Model State Machine (Spatie)
- Use `instanceof` for state checks, NEVER `->name` property.
- Example: `$appointment->status instanceof Completed` not `$appointment->status->name === 'completed'`

### Database & Laravel Cashier
- Subscriptions table uses `type` column, NOT `name` (Cashier convention).
- `REDIS_PASSWORD=null` must be the literal string `null` in docker-compose.yml, not an actual password.
- `trial_ends_at` on both `practices` and `subscriptions` tables (Stripe can override).
- Use `$practice->subscribed('default')` to check active subscription.
- Use `$practice->onTrial()` to check trial status.

### Docker & Deployment
- **Use `php:8.4-fpm` (Debian), NOT Alpine** — Alpine caused 75-minute builds and package naming issues.
- Never mount `.:/app` in volumes — overwrites vendor directory. Use named volumes instead.
- After any container restart, run `php artisan optimize` inside the container.
- Storage permissions: `chown -R www-data:www-data /app/storage && chmod -R 775 /app/storage`
- Bootstrap cache: `docker exec app chown -R www-data:www-data /app/bootstrap/cache`

---

## Multi-tenancy Implementation

**Model:** Single database, `practice_id`-based scoping
**Global Scope:** `BelongsToPractice` trait on all practice-scoped models
**Public Routes:** Resolved via token (intake, consent) — practice extracted from token record
**Auth Routes:** Resolved via `auth()->user()->practice_id` automatically

**Pattern:**
```php
// Every model (except Practice itself)
use App\Traits\BelongsToPractice;

class Patient extends Model {
    use BelongsToPractice;
}

// Automatic filtering in queries
Patient::all(); // only practice_id = current user's practice_id
```

---

## Deployment Workflow

### Development
```bash
# Make changes locally in WSL
git add .
git commit -m "your changes"
git push origin dev        # saves to GitHub, NO deployment
```

### Deploy to Production
```bash
git checkout master
git merge dev
git push origin master     # triggers GitHub Actions auto-deploy
```

### What Happens on `git push origin master`
1. GitHub Actions runs `.github/workflows/deploy.yml`
2. SSHes into 137.184.33.220 as root
3. Runs: `cd /opt/practiq && bash deploy.sh`
4. `deploy.sh`:
   - `docker compose build app --no-cache` (full rebuild)
   - Recreates app, nginx, queue, scheduler containers
   - Fixes storage/bootstrap permissions
   - Runs `php artisan optimize`
   - Reloads Caddy

### Manual Deployment (if needed)
- Full rebuild: `bash /opt/practiq/deploy.sh`
- Config/view only: `bash /opt/practiq/update.sh` (no Docker rebuild)

### Server Details
- **Path:** `/opt/practiq` (not `/home/alfre/healthcare-saas`)
- **Network:** `practiq_healthcare-saas` (Caddy connected for DNS resolution)
- **Caddy config:** `/opt/practiq/Caddyfile` — uses `healthcare-saas-nginx:80` by container name
- **Secrets required in GitHub:**
  - `SSH_HOST` = `137.184.33.220`
  - `SSH_USER` = `root`
  - `SSH_PRIVATE_KEY` = private key authorized on server

---

## Local Development

### Admin Test Account
- Email: `admin@healthcare.test`
- Password: `password`
- Practice ID: 1 (Green Valley Acupuncture)

### Database Access
- **Container:** `healthcare-saas-postgres`
- **User:** `healthcare`
- **Password:** `changeme`
- **Database:** `healthcare_saas`
- **Command:** `docker exec -it healthcare-saas-postgres psql -U healthcare -d healthcare_saas`
- **Note:** Use psql directly, NOT tinker (psysh has permission issues in container)

### Redis
- **Container:** `healthcare-saas-redis`
- **Password:** `changeme` (must match docker-compose.yml)

### Stripe
- **Note:** Stripe CLI unavailable on this network
- **Alternative:** `php artisan stripe:sync` (custom command)
- **Test Keys:** Already in .env (test mode only)

### Dev Server
```bash
php artisan serve
# http://localhost:8000/admin
# http://localhost:8000/book
```

---

## Current Project Status (March 2026)

### Live Features
- ✅ Multi-tenant practice management
- ✅ Online 5-step booking wizard
- ✅ Patient intake & consent forms (token-based)
- ✅ Clinical encounter notes (core + acupuncture extension)
- ✅ Checkout and Stripe payments
- ✅ Subscription billing (Solo/Clinic/Enterprise tiers)
- ✅ Herb & Product Inventory add-on ($19/month)
- ✅ Self-serve trial registration (30-day free)
- ✅ Trial enforcement middleware
- ✅ Trial countdown banner (warning colors)
- ✅ CSV patient importer with column mapper
- ✅ Low stock alerts (daily job)
- ✅ Dashboard with real-time metrics
- ✅ Data export (CSV ZIP and JSON formats)
- ✅ Export access for expired-trial accounts (30-day grace period)
- ✅ GitHub Actions CI/CD pipeline
- ✅ Docker deployment pipeline

### Test Coverage
- 95+ tests passing
- Integration tests for checkout, subscriptions, trial
- CSV import tests (16 cases)
- Registration flow tests

---

## Completed Modules

| Module | Status | Key Features |
|--------|--------|---|
| **Core Platform** | ✅ Complete | Multi-tenant practices, users, practitioners |
| **Scheduling** | ✅ Complete | Appointment types, state machine (scheduled→completed) |
| **Patients** | ✅ Complete | Base model, demographics, phone/email validation |
| **Intake & Consent** | ✅ Complete | Token-based public forms, signature capture |
| **Encounters** | ✅ Complete | Core notes + acupuncture extension table |
| **Checkout** | ✅ Complete | State machine (open→paid), line items, ledger |
| **Stripe Billing** | ✅ Complete | Subscriptions, state tracking, webhooks |
| **Trial System** | ✅ Complete | 30-day auto-creation, enforcement, countdown banner |
| **Inventory Add-on** | ✅ Complete | Products, categories, low-stock alerts |
| **CSV Importer** | ✅ Complete | Column mapper, preview, queued processing, history |
| **Data Export** | ✅ Complete | CSV ZIP & JSON formats, token-based downloads, 24-hour expiry |
| **Dashboard** | ✅ Complete | Metrics, revenue, appointment status breakdown |

---

## Immediate Next Steps

1. **Test CSV patient importer locally** — Verify parsing of various date/phone formats
2. **Deploy to production** — Via GitHub Actions (push to master)
3. **Fix inventory products seeding** — Currently showing 0 in demo data

---

## Planned Features (Short → Medium Term)

### Short Term (Next sprint)
- `demo.practiqapp.com` with Serenity Acupuncture pre-populated data
- Discipline-based feature visibility (hide acupuncture fields for massage therapists)
- Onboarding checklist for new practices
- Email service setup (Mailgun or Postmark)
- Legal documents (Terms, Privacy Policy)

### Medium Term
- Appointment reminders (SMS + email)
- Online payment at booking
- Patient portal (view appointments, fill forms)
- Massage therapy discipline module (with custom encounter fields)
- Chiropractic discipline module

### Long Term
- Telemedicine integration
- Insurance billing
- Referral tracking
- Staff scheduling (for clinics)

---

## Known Gotchas & Workarounds

### Network & Git
- **GitHub is HTTPS-only** — SSH blocked on this network. Use `git config url."https://".insteadOf git://`
- **Stripe CLI unavailable** — Use custom `php artisan stripe:sync` command instead

### Filament & Forms
- **Practice switcher is plain HTML POST form**, NOT Livewire — Livewire registration fails in render hooks
- **Tailwind doesn't compile for Filament** — Must use inline styles
- **TextColumn::nullable() is broken** — Use placeholder() instead
- **Render hooks in register() cause binding errors** — Must use boot()

### Docker & Storage
- **Never use `.:/app` mount** — Overwrites vendor directory, breaks everything
- **Volume mounts populate only on first creation** — Rebuilt images won't repopulate volumes
- **Storage permissions must be fixed after every rebuild** — `chown -R www-data:www-data /app/storage`
- **After container restart, run optimize** — Caches get stale without it

### Database
- **PostgreSQL RLS is disabled** — Don't rely on database-level security, use application scoping
- **BelongsToPractice uses qualified columns** — Prevents scope bypass in joins
- **withoutPracticeScope() is the only escape hatch** — Use sparingly, only for admin/system queries

---

## Directory Structure

```
app/
  Filament/Resources/        # Filament resource pages
    Patients/Schemas/, Tables/, Pages/
  Filament/Pages/
    Settings/ImportPatients.php    # CSV importer page
  Filament/Widgets/          # Dashboard widgets
  Http/
    Controllers/              # Auth, booking, public routes
    Middleware/RequiresActiveSubscription.php
  Jobs/
    ImportPatientsJob.php     # Async CSV processing
    SendLowStockAlert.php
  Models/
    Practice.php              # Core tenant model
    Appointment.php           # With state machine
    Patient.php               # With BelongsToPractice
    ImportHistory.php         # CSV import tracking
  Services/
    CSVImportService.php      # Date parsing, phone formatting
  Traits/
    BelongsToPractice.php     # Global scope + tenant checking
  Observers/                  # Model event listeners
  Providers/
    Filament/AdminPanelProvider.php  # Filament config

database/
  migrations/                 # All schema files
  seeders/
    DatabaseSeeder.php        # Core demo data (idempotent)
    DemoSeeder.php            # Serenity Acupuncture (30-day history)

resources/
  views/
    filament/
      pages/                  # Custom page views
      hooks/                  # Render hook views (trial-banner, etc)
    mail/
    livewire/public/          # Public booking, intake, consent

docker-compose.yml           # Local & production setup
Dockerfile                   # Multi-stage build (Debian base)
deploy.sh                    # Full rebuild script
update.sh                    # Config/cache-only script
```

---

## Version History

| Date | Version | Milestone |
|------|---------|-----------|
| Mar 27, 2026 | v0.8.0 | CSV patient importer complete |
| Mar 24, 2026 | v0.7.5 | Docker Debian migration, GitHub Actions |
| Mar 20, 2026 | v0.7.0 | Herb & Product Inventory add-on |
| Mar 15, 2026 | v0.6.0 | Trial system & countdown banner |
| Mar 10, 2026 | v0.5.0 | Checkout & Stripe billing |
| Mar 01, 2026 | v0.4.0 | Encounter notes (acupuncture) |
| Feb 20, 2026 | v0.3.0 | Scheduling & appointment types |
| Feb 01, 2026 | v0.2.0 | Patient intake & consent forms |
| Jan 15, 2026 | v0.1.0 | Core multi-tenant architecture |

---

## Critical Commands Reference

```bash
# Database
php artisan migrate              # Apply pending migrations
php artisan migrate:fresh --seed # Reset database + seed demo data
psql -U healthcare -d healthcare_saas  # Connect directly to DB

# Caching & Optimization
php artisan optimize:clear       # Clear all caches
php artisan config:cache        # Cache config (required in production)
php artisan route:cache         # Cache routes (required in production)
php artisan view:cache          # Cache views (required in production)

# Stripe
php artisan stripe:sync         # Sync Stripe data to local DB

# Testing
php artisan test                # Run all tests
php artisan test tests/Feature/CSVImportTest.php  # CSV tests only

# Docker
docker compose up -d            # Start all containers
docker compose down             # Stop all containers
docker compose build app --no-cache  # Rebuild app image
docker exec app php artisan optimize  # Run artisan in container
bash /opt/practiq/deploy.sh    # Full deploy on production server
bash /opt/practiq/update.sh    # Config-only update on production server
```

---

## Support & Contact

- **Developer:** Alfred (ELF Consulting)
- **Product:** Practiq (practiqapp.com)
- **GitHub:** healthcare-saas
- **Issues:** Use GitHub Issues for tracking bugs and features
