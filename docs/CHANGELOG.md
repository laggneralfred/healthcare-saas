# Practiq Changelog

## [April 12, 2026] — Major UX & Infrastructure Overhaul

### Infrastructure Fixes
- Fixed www.eurekakaratedo.org SSL certificate (missing DNS A record)
- Fixed app.practiqapp.com and demo.practiqapp.com DNS (Namecheap cPanel → BasicDNS migration, records restored)
- Fixed Docker network connectivity between Caddy and healthcare-saas
- Added SSH keepalive settings to prevent session timeouts
- Added tmux recommendation for persistent terminal sessions

### Database & Seeding
- Fixed all 15 factory files (Faker namespace resolution issues)
- Fixed fakerphp/faker moved to require in composer.json
- Removed --no-dev from Dockerfile so Faker available in production
- Added complete seeding for: intake submissions (199), encounters (381), acupuncture encounters (36), inventory products (30), inventory movements (166)
- Fixed SOAP fields missing from encounters table (migration added)
- Fixed IntakeSubmission array/string type errors in ViewIntakeSubmission
- Fixed demo practice trial expiration (is_demo bypass + 10yr trial)

### Navigation & Sidebar
- Reordered sidebar: Patients → Schedule → Communications → Inventory → Billing → Settings
- Fixed Filament v3 group ordering via AdminPanelProvider navigationGroups()
- Settings group pinned to bottom (sort 100)

### Encounter Page Redesign (Phase 2)
- Added encounter header widget: patient, practitioner, date, discipline, appointment time, status badge
- Two-column layout: Patient Context (left) + Clinical Notes (right)
- Patient context shows last 3 visits + intake summary
- Action buttons: Save Draft, Complete Encounter, Proceed to Checkout
- Auto-population of discipline from practitioner specialty
- Fixed invalid heroicon names (calendar-plus, document-plus)

### Patient Record Redesign (Phase 3)
- Patient header bar: name, DOB/age, gender, phone, email, Active/New/Inactive status badge, Primary Concern
- Quick Summary panel: chief concern, onset, pain scale (color-coded), last visit summary, next appointment
- Visit History tab: encounters listed with date, discipline, practitioner, chief complaint, pain progression, status
- Tabs: Visit History | Intake & History | Appointments | Demographics | Billing
- Appointment rows linked to related encounters

### Demo Data Story
- Named demo patients with coherent clinical journeys
- Jane Smith: 6 acupuncture visits showing pain progression 8/10 → 1/10
- Realistic SOAP notes with 7 clinical presentations
- Appointments linked to encounters and checkout sessions

### Spec Documentation
- Created docs/practiq-ux-spec.md with full UX redesign specification
- Covers role-based dashboards, front desk screen, clinical workflow map
- Implementation phases 1-5 documented
- Open questions for product decisions listed
