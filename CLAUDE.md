# Healthcare SaaS — CLAUDE.md

## Project Overview

Multi-tenant healthcare SaaS platform built with **Laravel 13 + Filament v5 + Livewire v4**.
Migrated from an Odoo prototype (19 modules, 35 models).

---

## Architecture: Modular SaaS Design

### Core Platform (discipline-agnostic)
The core platform is **specialty-agnostic**. Every core model is designed to work
across any healthcare discipline without modification.

**Core models (no specialty logic):**
- `Practice` — tenant root; owns all data
- `User` + `Practitioner` — authentication and scheduling actors
- `Patient` — any patient, any discipline
- `AppointmentType` — named slot type, no clinical meaning
- `Appointment` — scheduling record with state machine
- `IntakeSubmission` — token-based pre-visit form
- `ConsentRecord` — token-based consent signature
- `Encounter` — visit record; **extended by specialty modules**
- `CheckoutSession` + `CheckoutLine` — payment processing

### Specialty Modules (extend the core)
Specialty modules add discipline-specific clinical fields by **extending the core
`Encounter` model** via a one-to-one relationship or polymorphic extension table.
They never modify core migrations.

| Module | Status | Encounter extension table |
|--------|--------|--------------------------|
| `hc_acupuncture` | **In progress (current)** | `acupuncture_encounters` |
| `hc_massage` | Planned | `massage_encounters` |
| `hc_chiropractic` | Planned | `chiropractic_encounters` |
| `hc_physical_therapy` | Planned | `pt_encounters` |

**Extension pattern:**
```php
// core Encounter — discipline-agnostic
Table: encounters
  - id, practice_id, patient_id, appointment_id (unique)
  - practitioner_id, status, encounter_date, completed_on
  - chief_concern, visit_summary, treatment_notes, notes

// acupuncture module adds ONE extra table
Table: acupuncture_encounters
  - id, encounter_id (FK, unique)
  - tongue_observation, pulse_diagnosis
  - tcm_pattern, meridians_treated, needle_count
  - moxa_used (boolean), cupping_used (boolean)
```

### Design Rules for Core Models
1. **No specialty field names** — `chief_concern` not `acupuncture_chief_concern`
2. **No specialty FKs** — core models never reference module tables
3. **practice_id on every table** — all data is tenant-scoped
4. **Modules are additive** — installing a module adds tables, never modifies core
5. **AppointmentType is the specialty bridge** — a practice configures types that
   match their discipline; no specialty enum needed in core

---

## Tech Stack

| Layer | Package | Version |
|-------|---------|---------|
| Framework | Laravel | 13.x |
| Admin UI | Filament | 5.x |
| Reactive UI | Livewire | 4.x |
| State machine | spatie/laravel-model-states | 2.12.x |
| CSS | Tailwind CSS | 4.x (via Vite) |
| Database | PostgreSQL (dev + prod) |

---

## Directory Conventions

```
app/
  Models/                  # Eloquent models
    States/Appointment/    # Spatie state classes
  Filament/Resources/      # One folder per resource (Filament v5 style)
    Patients/
      Schemas/             # Form schema class
      Tables/              # Table class
      Pages/               # List/Create/Edit pages
  Livewire/
    Public/                # Unauthenticated public forms (intake, consent)
  Traits/
    HasAccessToken.php     # Shared token generation (intake + consent)

resources/views/
  layouts/
    public.blade.php       # Layout for unauthenticated public pages
  livewire/public/         # Blade views for public Livewire components
```

---

## Multi-tenancy

- Every model has `practice_id` FK — **always include it in queries**
- `BelongsToPractice` global scope (Eloquent layer) enforces tenant isolation on all scoped models
- `SetPostgresTenantContext` middleware (web group) writes `app.practice_id` to the PostgreSQL session variable on every web request
- PostgreSQL Row Level Security (RLS) enforces isolation at the database level as a second defence layer — `FORCE ROW LEVEL SECURITY` is enabled on all practice-scoped tables
- Public routes (/intake, /consent): practice resolved from the token record; component's `mount()` must call `DB::statement("SELECT set_config('app.practice_id', ?, false)", [$id])` after resolving the practice from the token

### Two-database-user model

| User | Privileges | Used by |
|------|-----------|---------|
| `postgres` | Superuser, BYPASSRLS | Tests (`phpunit.xml`), `migrate:fresh` during testing |
| `healthcare` | App user, subject to RLS | Web app `.env`, `migrate:fresh --seed` in dev (seeder sets `app.practice_id` before each practice section) |

---

## Public Token Routes (no auth)

| Route | Component | Purpose |
|-------|-----------|---------|
| `GET /intake/{token}` | `Livewire\Public\IntakeForm` | Patient pre-visit intake |
| `GET /consent/{token}` | `Livewire\Public\ConsentForm` | Patient consent signature |

Tokens are 64-char URL-safe strings. Unique DB constraint enforced.
No expiry currently (add if compliance requires it).

---

## Build Progress

| Week | Focus | Status |
|------|-------|--------|
| 1 | Foundation: Practice, User, Practitioner, Filament panel | ✅ Done |
| 2 | Scheduling: AppointmentType, Appointment + state machine | ✅ Done |
| 3 | Patient, Intake, Consent, public token forms | ✅ Done |
| 4 | Encounter (core + acupuncture extension) | ✅ Done |
| 5–6 | Checkout state machine + Stripe subscription billing | ✅ Done |
| 7 | Reporting, polish, prod deploy | ✅ Done |

---

## Week 7 Completion Summary

### Reporting
- **DashboardPage**: Real-time practice metrics dashboard showing:
  - Monthly appointment counts (scheduled/completed/pending)
  - Patient metrics (total, new this month)
  - Revenue metrics (paid, pending, by practitioner)
  - Appointment status breakdown chart
  - Revenue by practitioner breakdown

### Polish
- **Model Enhancements**:
  - Added query scopes (byPractice, byStatus, paid, pending, thisMonth)
  - Added calculated properties (amount_due, is_fully_paid, is_partially_paid)
  - Added model-level validation for amount constraints
  - Enhanced Practice model with subscription helpers
  - Added practitioner limit checking based on subscription plan

- **API Endpoints**:
  - Created PracticeStatsController for programmatic access to metrics
  - `/api/practices/{practice}/stats` endpoint with comprehensive data
  - Sanctum-authenticated API for external integrations

- **Testing**:
  - Created comprehensive CheckoutSessionTest suite (13+ test cases)
  - Test state management, calculations, and validation
  - Test query scopes and relationships

### Production Deployment
- **Environment Configuration**:
  - `.env.production.example` with PostgreSQL, Redis, S3 configuration
  - Support for production Stripe live keys
  - Security hardening (HTTPS, secure cookies, HSTS)

- **Deployment Guide** (`DEPLOYMENT.md`):
  - 20+ item pre-deployment checklist
  - Step-by-step deployment procedure
  - Nginx and Apache configuration examples
  - Database, queue, and caching setup
  - Health check and monitoring procedures
  - Post-deployment maintenance schedule
  - Rollback procedures

## Dev Setup

```bash
cd ~/healthcare-saas
composer install
php artisan migrate --seed      # or migrate:fresh --seed to reset
npm install && npm run build
php artisan serve
# Admin panel: http://localhost:8000/admin
# Dashboard: http://localhost:8000/admin/dashboard-page
# Billing: http://localhost:8000/admin/billing
# API Stats: GET /api/practices/{id}/stats (requires Sanctum token)
# Login: admin@healthcare.test / password
```

## Production Deployment

See `DEPLOYMENT.md` for comprehensive production deployment guide including:
- Pre-deployment checklist (environment, database, Stripe, security)
- Nginx/Apache configuration
- Post-deployment monitoring and maintenance
- Disaster recovery procedures
