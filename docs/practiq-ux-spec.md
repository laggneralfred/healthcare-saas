# Practiq UX & Workflow Redesign Specification
**Version:** 1.0  
**Date:** April 12, 2026  
**Author:** Alfred Laggner  
**Project:** Practiq Healthcare SaaS — demo.practiqapp.com

---

## 1. Overview & Goals

Practiq currently has a single-role UI that mixes clinical and administrative 
tasks in one sidebar. This spec defines a role-based redesign that matches 
the real-world workflow of a multi-practitioner integrative health clinic.

### Core Workflow Principle
> The UI must follow the natural clinical day — not the database structure.

### Target User Roles
1. **Admin / Practice Owner** — full access, reports, settings
2. **Practitioner** — clinical focus, their patients only
3. **Front Desk** — scheduling, checkout, communications

---

## 2. Clinical Workflow Map

### Established Patient Visit
```
Appointments (today's schedule)
    ↓
Patient arrives → Front Desk checks in patient → Notifies practitioner
    ↓
Practitioner opens Patient Record
    → Sees: demographics, last visit summary, today's flags/notes
    ↓
Treatment
    → Opens Encounter (pre-filled: patient, date, practitioner, discipline)
    → Reviews intake/history in sidebar panel
    → Records SOAP notes + discipline-specific treatment notes
    → Notes supplements/products recommended
    ↓
Patient to Front Desk
    → Products sold, payment taken
    → Next appointment booked
    → Receipt emailed
    ↓
Practitioner adds final notes / follow-up instructions
```

### New Patient Visit
```
Front Desk creates patient record
    → Sends intake form (email/SMS link or tablet)
    ↓
Patient completes intake form
    ↓
Practitioner reviews intake before/during visit
    ↓
First Encounter (same flow as above, intake visible in sidebar)
```

---

## 3. Role-Based Dashboards

### 3.1 Front Desk Dashboard

**Primary screen when front desk logs in.**

#### Layout
```
┌─────────────────────────────────────────────────────────┐
│  TODAY — Monday April 14          [+ New Appointment]   │
├──────────────────────┬──────────────────────────────────┤
│  TODAY'S SCHEDULE    │  ACTION ITEMS                    │
│                      │                                  │
│  9:00 Jane Smith     │  ⚠ 3 intake forms missing        │
│       Acu Anna  ●    │  ⚠ 2 consents pending           │
│  9:30 Bob Jones      │  💳 1 outstanding payment        │
│       Dr. Bone       │                                  │
│  10:00 Mary Lee  ✓   │  QUICK ACTIONS                   │
│       PT Paul        │  [Check In Patient]              │
│  ...                 │  [New Patient]                   │
│                      │  [Send Intake Form]              │
│  [All Practitioners] │  [Process Payment]               │
│  [Acu Anna only]     │                                  │
├──────────────────────┴──────────────────────────────────┤
│  WAITING ROOM                                           │
│  Mary Lee — arrived 9:52am — waiting for PT Paul        │
└─────────────────────────────────────────────────────────┘
```

#### Front Desk Sidebar Navigation
```
📅 Schedule
   Today's Appointments
   All Appointments
   Appointment Types

👥 Patients
   Patient List
   New Patient
   Intake Forms (pending/missing highlighted)
   Consent Records

💬 Communications
   Send Message
   Message Templates
   Message Logs

💳 Billing
   Checkout / POS
   Checkout Sessions
   Service Fees
   Outstanding Payments

⚙ Settings (collapsed by default)
   Practitioners
   Practices
   Subscription
   Audit Log
```

---

### 3.2 Practitioner Dashboard

**Primary screen when practitioner logs in.**

#### Layout
```
┌─────────────────────────────────────────────────────────┐
│  Good morning, Acu Anna — Monday April 14               │
├─────────────────────────────────────────────────────────┤
│  MY PATIENTS TODAY                                      │
│                                                         │
│  9:00  Jane Smith      Acupuncture  ● Checked In        │
│        Last: Apr 7 — Chronic back pain, improving       │
│        [Open Record]  [Start Encounter]                 │
│                                                         │
│  10:30 Bob Jones       Acupuncture  ○ Scheduled         │
│        Last: Mar 28 — Insomnia, 6 sessions completed    │
│        [Open Record]  [Start Encounter]                 │
│                                                         │
│  2:00  New Patient     Acupuncture  — Intake Pending    │
│        Maria Garcia — intake form sent, not submitted   │
│        [View Intake]  [Start Encounter]                 │
└─────────────────────────────────────────────────────────┘
```

#### Practitioner Sidebar Navigation
```
🏠 Dashboard (My Day)

📅 My Schedule
   Today
   Full Calendar

👥 My Patients
   Patient List
   Visits / Encounters
   Medical History
   Consent Records

📦 Inventory
   Products (view/sell only)
   Movements

⚙ Settings (collapsed)
   My Profile
   Practices
```

---

### 3.3 Admin Dashboard

**Full access — practice owner view.**

#### Layout
```
┌─────────────────────────────────────────────────────────┐
│  Eureka Integrated Health — Monday April 14             │
├──────────────┬──────────────┬──────────────┬────────────┤
│  TODAY       │  THIS WEEK   │  THIS MONTH  │  ALERTS    │
│  12 appts    │  47 appts    │  $8,420 rev  │  3 items   │
│  8 patients  │  32 patients │  89 patients │            │
├──────────────┴──────────────┴──────────────┴────────────┤
│  ALL PRACTITIONERS TODAY                                │
│  Acu Anna:  4 patients  |  Dr. Bone:   3 patients      │
│  PT Paul:   3 patients  |  S. Massage: 2 patients      │
└─────────────────────────────────────────────────────────┘
```

#### Admin Sidebar — Full Navigation
```
🏠 Dashboard

📅 Schedule
   Today's Appointments
   All Appointments
   Appointment Types

👥 Patients
   All Patients
   Visits
   Medical History
   Consent Records
   Import / Export

💬 Communications
   Overview
   Message Templates
   Communication Rules
   Message Logs

💳 Billing
   Checkout Sessions
   Service Fees
   Reports

📦 Inventory
   Products
   Movements

⚙ Settings
   Practitioners
   Practices
   Subscription
   Audit Log
```

---

## 4. Encounter Page Redesign

### Current Problems
- No header showing who/what/when
- SOAP fields disconnected from discipline tabs
- No patient history visible while writing notes
- Products and next appointment require leaving the page

### Redesigned Encounter Layout

```
┌─────────────────────────────────────────────────────────────────────┐
│  ENCOUNTER HEADER                                                   │
│  Patient: Jane Smith          Practitioner: Acu Anna               │
│  Date: April 14, 2026         Discipline: Acupuncture              │
│  Appointment: 9:00 AM         Status: [Draft ▼]                    │
├────────────────────────────┬────────────────────────────────────────┤
│  PATIENT CONTEXT (left)    │  TODAY'S NOTES (center)               │
│                            │                                        │
│  Last Visit: Apr 7         │  Chief Complaint                      │
│  "Chronic back pain,       │  [                              ]     │
│   responding well"         │                                        │
│                            │  SOAP Notes                           │
│  Visit before: Mar 31      │  S: Subjective  [            ]       │
│  "Started acu protocol"    │  O: Objective   [            ]       │
│                            │  A: Assessment  [            ]       │
│  Intake Summary:           │  P: Plan        [            ]       │
│  • Back pain 3 years       │                                        │
│  • No medications          │  ── Discipline Tab ──                 │
│  • No red flags            │  [Acupuncture Notes]                  │
│                            │  Points used:                         │
│  ── Products ──            │  [                              ]     │
│  [+ Add Product]           │                                        │
│  • Turmeric 500mg x2       │  Needle technique:                    │
│  • Back Support Formula    │  [                              ]     │
│                            │                                        │
│  ── Next Appointment ──    │  TCM Diagnosis:                       │
│  [Book Follow-up]          │  [                              ]     │
│  Apr 21, 9:00 AM ✓         │                                        │
└────────────────────────────┴────────────────────────────────────────┘
│  [Save Draft]    [Complete Encounter]    [Proceed to Checkout]      │
└─────────────────────────────────────────────────────────────────────┘
```

### Encounter Auto-Population Rules
- **Discipline** auto-set from appointment type or practitioner's primary discipline
- **Practitioner** auto-set from appointment
- **Date** auto-set to appointment date
- **Discipline tab** auto-opens to match discipline (Acupuncture tab opens for Acu Anna)
- **Patient context panel** auto-loads last 3 visits + intake summary

---

## 5. Patient Record Redesign

### Current Problems
- Opens to blank edit form
- No visit history visible
- No quick summary of patient status

### Redesigned Patient Record Layout

```
┌─────────────────────────────────────────────────────────────────────┐
│  Jane Smith  |  DOB: Mar 15, 1978 (47)  |  F  |  Active Patient    │
│  📞 (415) 555-0123  |  ✉ jane@email.com  |  [Edit]  [New Encounter]│
├─────────────────┬───────────────────────────────────────────────────┤
│  QUICK SUMMARY  │  VISIT HISTORY                                    │
│                 │                                                   │
│  Chief concern: │  Apr 14  Acupuncture — Acu Anna    [Draft]       │
│  Chronic back   │  Apr 7   Acupuncture — Acu Anna    Complete ↗    │
│  pain           │  Mar 31  Acupuncture — Acu Anna    Complete ↗    │
│                 │  Mar 24  Acupuncture — Acu Anna    Complete ↗    │
│  Since: 3 yrs   │                                                   │
│  Pain scale: 6  │  INTAKE FORM                                      │
│                 │  Submitted: Jan 15, 2026           View ↗        │
│  Last visit:    │                                                   │
│  Apr 7          │  UPCOMING APPOINTMENTS                            │
│  "Improving"    │  Apr 21  9:00 AM  Acu Anna                       │
│                 │  May 5   9:00 AM  Acu Anna                       │
│  Alerts: None   │                                                   │
├─────────────────┴───────────────────────────────────────────────────┤
│  Tabs: Demographics | Medical History | Consent | Communications    │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 6. Demo Data Story

The demo database should tell a **coherent clinical story** — not random disconnected records.

### Demo Practice: Eureka Integrated Health
- Multi-discipline: Acupuncture, Massage, Physiotherapy, Chiropractic
- 4 practitioners (one per discipline)
- 1 admin/front desk user

### Demo Patients (named, with full journey)

#### Patient 1: Jane Smith
- Established patient, 6 acupuncture visits for chronic back pain
- Has intake form, consent, 6 encounters with SOAP notes showing progression
- Latest visit: 2 weeks ago, next appointment booked
- Products purchased: herbal formula

#### Patient 2: Robert Johnson
- Physiotherapy patient, post-ACL surgery rehabilitation
- 8 visits, clear progression in notes (Week 1 pain 8/10 → Week 8 pain 2/10)
- Has intake, consent, checkout sessions paid

#### Patient 3: Maria Garcia
- New patient, intake form pending
- First appointment tomorrow
- Demonstrates new patient onboarding flow

#### Patient 4: David Chen
- Massage therapy, stress/tension
- 3 visits, supplements sold each time
- Outstanding payment on last visit (demonstrates billing alert)

#### Patient 5-50: Supporting cast
- Mix of disciplines, visit histories, statuses
- Some with missing intakes (demonstrates front desk action items)
- Some with upcoming appointments

---

## 7. Implementation Plan

### Phase 1 — Demo Data Story (immediate, Haiku)
- [ ] Rewrite DatabaseSeeder with named patients and coherent journeys
- [ ] Ensure appointments → encounters → checkout are linked per patient
- [ ] Populate discipline field from practitioner type
- [ ] Add progression to SOAP notes (visit 1 vs visit 6 should differ)

### Phase 2 — Encounter Page Header & Context (Sonnet)
- [ ] Add header bar: patient, practitioner, discipline, date, status
- [ ] Auto-populate discipline from appointment/practitioner
- [ ] Auto-open correct discipline tab
- [ ] Add patient context left panel (last 3 visits + intake summary)
- [ ] Add products panel (sell from encounter)
- [ ] Add next appointment booking from encounter

### Phase 3 — Patient Record Redesign (Sonnet)
- [ ] Redesign patient record to show quick summary + visit history on open
- [ ] Add upcoming appointments panel
- [ ] Add "New Encounter" button from patient record

### Phase 4 — Role-Based Dashboards (Sonnet)
- [ ] Implement Filament role/shield or custom middleware for roles
- [ ] Front Desk dashboard with today's schedule + action items
- [ ] Practitioner dashboard with my patients today
- [ ] Admin dashboard with practice overview

### Phase 5 — Front Desk Screen (Sonnet)
- [ ] Today's schedule with all practitioners
- [ ] Check-in workflow (mark arrived, notify practitioner)
- [ ] Waiting room panel
- [ ] Action items (missing intakes, pending consents, outstanding payments)

---

## 8. Technical Notes

### Filament Resources to Modify
- `EncounterResource` — header, auto-population, context panel
- `PatientResource` — quick summary layout, visit history
- `AppointmentResource` — check-in status, waiting room
- New: `DashboardPage` (role-aware)
- New: `FrontDeskPage`

### Database Changes Needed
- `encounters.discipline` — add if not present (copy from appointment type)
- `appointments.checked_in_at` — timestamp for front desk check-in
- `appointments.status` — scheduled/checked-in/in-progress/complete/cancelled

### Roles & Permissions
- Use Filament Shield or custom `role` column on `users` table
- Roles: `admin`, `practitioner`, `front_desk`
- Practitioners only see their own patients by default
- Front desk sees all appointments but not clinical notes

---

## 9. Open Questions for Decision

1. Should practitioners see ALL patients or only their own?
2. Should front desk see clinical notes (SOAP) or only scheduling/billing?
3. Should the intake form be a separate public URL (patient fills on phone) 
   or filled in-app by front desk?
4. Multi-location support needed now or later?
5. Should products sale happen inside encounter or always at checkout?

---

*This spec should be reviewed before implementation begins on Phase 2+.  
Phase 1 (demo data) can proceed immediately.*
