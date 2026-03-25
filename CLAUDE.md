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
| Database | SQLite (dev) / PostgreSQL (prod) |

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
- Middleware (TBD): resolves current practice from authenticated user
- Filament resources: scope dropdowns to current practice in production
- Public routes (/intake, /consent): practice resolved from the token record

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
| 7 | Reporting, polish, prod deploy | Pending |

---

## Dev Setup

```bash
cd ~/healthcare-saas
composer install
php artisan migrate --seed      # or migrate:fresh --seed to reset
npm install && npm run build
php artisan serve
# Admin panel: http://localhost:8000/admin
# Login: admin@healthcare.test / password
```
