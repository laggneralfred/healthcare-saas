# Practiq — Healthcare SaaS Platform

## Summary

**Practiq** is a multi-tenant practice management SaaS platform for solo health practitioners (acupuncturists, massage therapists, chiropractors, physiotherapists). Built by Alfred, a retired health practitioner, it competes with Jane App and SimplePractice.

**Current Status (April 2026):** MVP launched and stabilized. All 133 tests passing. Demo environment ready with automated reset. Multi-tenant practice management, online booking, patient forms, clinical documentation, Stripe payments, and data export are fully functional.

**Core Stack:** Laravel 13 + Filament v5 admin UI + Livewire v3 reactive components + PostgreSQL + Docker (Debian) + DigitalOcean. Production deployed via GitHub Actions CI/CD.

**Architecture:** Single-database multi-tenancy with application-level `practice_id` scoping via `BelongsToPractice` trait on all models. State machine pattern for appointments and checkouts. Queued jobs for CSV imports and low-stock alerts.

**Key Rules:** Never break multi-tenancy (all queries are scoped). Never use Alpine in Docker (causes 75-minute builds). Never call `parent::boot()` in AdminPanelProvider (Filament has no boot method). Never mount `.:/app` in Docker volumes (overwrites vendor). Never use `.nullable()` on Filament TextColumn (causes hidden errors — use `.placeholder()` instead). DemoModeMiddleware blocks POST/PUT/PATCH/DELETE **and** GET /create + /edit — always block both write methods and write pages.

**Quick Start:** `php artisan serve` → http://localhost:8000/admin | Email: `admin@healthcare.test` | Password: `password` | See CLAUDE.md for full dev/deployment workflow.

---

## Project Overview

**Product:** Practiq (practiqapp.com) — multi-tenant healthcare SaaS for solo health practitioners
**Solo Developer:** Alfred, retired health practitioner, ELF Consulting
**Live:** app.practiqapp.com (production)
**Demo:** demo.practiqapp.com (with automated reset)
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
- **`Section` moved to `Filament\Schemas\Components\Section`** — the old `Filament\Forms\Components\Section` does not exist in v5.
- **Custom Page form method is `form(Schema $schema): Schema`** — `getFormSchema(): array` is the v4 API and silently does nothing in v5. Import `Filament\Schemas\Schema` and return `$schema->components([...])`.
- **Always add `->live()` to `FileUpload` when using `->afterStateUpdated()`** — without it the callback never fires.
- **Every form field on a custom Page needs a matching public Livewire property** — Filament v5 binds field state directly to public properties.
- **Custom Pages with public properties must declare a `rules()` method** — Livewire v3 throws `MissingRulesException` without it.
- **Never call `$this->form->getState()` inside `afterStateUpdated` callbacks** — `getState()` triggers Livewire validation too early.
- **`Filament\Forms\Components\Grid` moved to `Filament\Schemas\Components\Grid` in v5** — the old namespace does not exist. Search with: `grep -rn 'use Filament\\Forms\\Components\\Grid' app/`
- **`Filament\Actions\Action::successNotification()` requires a `Notification` object argument in v5** — calling it with no args throws a fatal error. Use `->successNotificationTitle('message')` instead.
- **`Filament\Tables\Actions\Action` does not exist in v5** — use `Filament\Actions\Action` for custom actions. Only `EditAction`, `DeleteAction`, `ViewAction`, `BulkActionGroup`, `ForceDeleteAction`, `RestoreAction` remain under `Filament\Tables\Actions`.

### Demo Mode
- **DemoModeMiddleware must block BOTH write HTTP methods AND write GET pages** — blocking only POST/PUT/PATCH/DELETE is not enough; users can still navigate to `/create` and `/edit` URLs directly.
- The GET `/create`/`/edit` block must be **outside and before** the write-method check. If it is nested inside the write-method `if`, it will never run on GET requests.
- Pattern: check `is_demo`, then early-return for login/logout, then check `$isWriteMethod || $isWritePage`, then redirect to dashboard with notification.

### Model State Machine (Spatie)
- Use `instanceof` for state checks, NEVER `->name` property.
- Example: `$appointment->status instanceof Completed` not `$appointment->status->name === 'completed'`

### Database & Laravel Cashier
- Subscriptions table uses `type` column, NOT `name` (Cashier convention).
- `REDIS_PASSWORD=null` must be the literal string `null` in docker-compose.yml, not an actual password.
- `trial_ends_at` on both `practices` and `subscriptions` tables.
- Use `$practice->subscribed('default')` to check active subscription.
- Use `$practice->onTrial()` to check trial status.

### Docker & Deployment
- **Use `php:8.4-fpm` (Debian), NOT Alpine** — Alpine caused 75-minute builds.
- Never mount `.:/app` in volumes — overwrites vendor directory. Use named volumes instead.
- After any container restart, run `php artisan optimize` inside the container.
- Storage permissions: `chown -R www-data:www-data /app/storage && chmod -R 775 /app/storage`

---

## Multi-tenancy Implementation

**Model:** Single database, `practice_id`-based scoping
**Global Scope:** `BelongsToPractice` trait on all practice-scoped models
**Public Routes:** Resolved via token (intake, consent) — practice extracted from token record
**Auth Routes:** Resolved via `auth()->user()->practice_id` automatically

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

---

## Current Project Status (April 2026)

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
- ✅ Data export (CSV ZIP and JSON formats)
- ✅ Demo system: DemoModeMiddleware, DemoSeeder, ResetDemoDataJob, ResetDemoCommand, /demo-login route
- ✅ demo.practiqapp.com live with SSL
- ✅ GitHub Actions CI/CD pipeline
- ✅ Communications & Messaging System — Sprint 1 (email reminders, templates, rules, log, Filament UI)

### Test Coverage
- **147/147 tests passing (100%)**
- Integration tests for checkout, subscriptions, trial, registration
- CSV import tests
- Data export tests (CSRF excluded for testing)
- Filament smoke tests (Index/Edit pages for all resources)
- PracticeFactory sets `trial_ends_at = +30 days` so test practices pass RequiresActiveSubscription middleware
- Communications: template rendering, rule timing, send job (success/fail/opt-out/no-email), dispatch windowing, duplicate prevention, multi-tenancy isolation

### Pending Production Fixes (applied via docker cp — not yet in git)
- **DemoModeMiddleware** — GET /create and /edit block deployed manually; needs permanent rebuild via GitHub Actions to survive container restarts
- **InventoryProductForm** — Grid namespace fixed on disk; needs rebuild to confirm clean

---

## Completed Modules

| Module | Status | Key Features |
|--------|--------|---|
| **Core Platform** | ✅ Complete | Multi-tenant practices, users, practitioners |
| **Scheduling** | ✅ Complete | Appointment types, state machine (scheduled→completed) |
| **Patients** | ✅ Complete | Base model, demographics, extension fields |
| **Intake & Consent** | ✅ Complete | Token-based public forms, signature capture |
| **Encounters** | ✅ Complete | Core notes + acupuncture extension table |
| **Checkout** | ✅ Complete | State machine (open→paid), line items, ledger |
| **Stripe Billing** | ✅ Complete | Subscriptions, state tracking, webhooks |
| **Trial System** | ✅ Complete | 30-day auto-creation, enforcement, countdown banner |
| **Inventory Add-on** | ✅ Complete | Products, categories, low-stock alerts |
| **CSV Importer** | ✅ Complete | Column mapper, preview, queued processing |
| **Data Export** | ✅ Complete | CSV ZIP & JSON formats, token-based downloads |
| **Demo System** | ✅ Complete | Reset job/command, demo-login, middleware |
| **Communications — Sprint 1** | ✅ Complete | MessageTemplate, CommunicationRule (flexible timing), MessageLog, PatientCommunicationPreference, DispatchAppointmentRemindersJob (every 15 min), SendAppointmentReminderJob (opt-out aware), AppointmentReminderMail (branded HTML), Filament resources, manual send action, patient prefs UI |

---

## Immediate Next Steps

1. **Deploy to production** — Merge dev → master, push to trigger GitHub Actions; this permanently deploys DemoModeMiddleware fix, all Filament v5 namespace fixes, and the Communications sprint
2. **Configure Mailgun on production** — Set `MAIL_MAILER=mailgun`, `MAILGUN_DOMAIN`, `MAILGUN_SECRET` in `/opt/practiq/.env` on the server
3. **Run `php artisan demo:reset`** on production to seed default communication templates for the demo practice
4. **Communications Sprint 2** — Mailgun webhook for delivery/bounce status updates, SMS via Twilio, opt-out landing page
5. **Discipline-based feature visibility** — Hide acupuncture fields for non-acupuncture practitioners

---

## Critical Commands Reference

```bash
# Demo Management
php artisan demo:reset           # Reset demo data to Serenity Acupuncture baseline
php artisan test                # Run all 133 tests

# Database
php artisan migrate              # Apply pending migrations
php artisan migrate:fresh --seed # Reset database + seed demo data

# Docker
bash /opt/practiq/deploy.sh    # Full deploy on production server
bash /opt/practiq/update.sh    # Config-only update on production server
```
