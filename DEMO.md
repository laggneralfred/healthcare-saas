# Healthcare SaaS Demo Walkthrough

This document describes the demo data and provides a guided walkthrough of the application.

## Quick Start

### Seeding Demo Data

To populate the database with realistic demo data for "Serenity Acupuncture & Wellness":

```bash
php artisan db:seed --class=DemoSeeder
```

This creates:
- **Practice**: Serenity Acupuncture & Wellness (America/Los_Angeles timezone)
- **Practitioner**: Dr. Sarah Chen, L.Ac.
- **Patients**: 15 realistic patient records
- **Appointment Types**: 4 types with varying durations and prices
- **Appointments**: 40 total (30 historical, 5 today, 5 upcoming this week)
- **Encounters**: Full clinical encounters with SOAP notes and acupuncture point data
- **Checkouts**: All completed appointments have paid checkout sessions

### Login Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | demo@serenity.test | password |
| Practitioner | sarah@serenity.test | password |

### Booking URL

Public booking page (no login required):
```
/book/serenity-acupuncture
```

---

## Demo Practice Details

### Practice Info
- **Name**: Serenity Acupuncture & Wellness
- **Slug**: serenity-acupuncture
- **Timezone**: America/Los_Angeles
- **Status**: Active with solo subscription

### Practitioner
- **Name**: Dr. Sarah Chen
- **License**: L.Ac. CA-12847
- **Specialty**: Acupuncture & Oriental Medicine
- **Status**: Active

---

## Appointment Types

| Type | Duration | Price | Service Code |
|------|----------|-------|--------------|
| Initial Consultation | 90 min | $150.00 | INITIAL |
| Follow-up Treatment | 60 min | $95.00 | FOLLOWUP |
| Stress & Anxiety Protocol | 75 min | $110.00 | STRESS |
| Community Acupuncture | 45 min | $45.00 | COMMUNITY |

---

## Sample Patients

The demo includes 15 patients with realistic names and contact information:

1. James Patterson - (415) 555-0110
2. Lisa Cohen - (415) 555-0111
3. Michael Rodriguez - (415) 555-0112
4. Emma Williams - (415) 555-0113
5. David Park - (415) 555-0114
6. Sarah Thompson - (415) 555-0115
7. Robert Martinez - (415) 555-0116
8. Jennifer Lee - (415) 555-0117
9. Christopher Johnson - (415) 555-0118
10. Maria Gonzalez - (415) 555-0119
11. Daniel Anderson - (415) 555-0120
12. Michelle Brown - (415) 555-0121
13. Kevin Taylor - (415) 555-0122
14. Patricia White - (415) 555-0123
15. Brian Miller - (415) 555-0124

---

## Appointment Data Breakdown

### Historical Appointments (30 completed)
- **Status**: completed
- **Date Range**: Past 6 months
- **Associated Data**:
  - Encounter with SOAP notes (Subjective, Objective, Assessment, Plan)
  - AcupunctureEncounter with:
    - TCM diagnosis
    - Acupuncture points used (LI4, ST36, SP6, LV3, PC6, etc.)
    - Needle count (8-14 needles)
    - Treatment protocol notes
  - Intake submission (complete, submitted 1 day before)
  - Consent record (complete, signed 1 day before)
  - Paid checkout session (card or cash)

### Today's Appointments (5 total)
- **Date**: Today (appointment times 09:00 - 17:00)
- **Status Breakdown**:
  - 2x Scheduled (no encounter/checkout)
  - 2x In Progress (no encounter/checkout)
  - 1x Completed (with full encounter and paid checkout)
- **Associated Data**: Intake/Consent as pending or complete based on status

### Upcoming Week's Appointments (5 total)
- **Date Range**: Next 7 days
- **Status**: All scheduled
- **Associated Data**: Intake/Consent as pending (not yet submitted)

---

## Demo Walkthrough (5 minutes)

### 1. Public Booking Flow (2 minutes)

Navigate to `/book/serenity-acupuncture` and walk through the 5-step booking wizard:

**Step 1: Appointment Type**
- Show the 4 appointment types with durations and prices
- Click "Initial Consultation" ($150, 90 min)

**Step 2: Practitioner**
- Select "Dr. Sarah Chen, L.Ac." or leave as "Any Available"
- Click next

**Step 3: Calendar & Time Slots**
- Show the calendar with available dates
- Select a date in the current week
- Highlight the 30-minute time slots (09:00, 09:30, 10:00, etc.)
- Select an available 2-hour block for the 90-min appointment

**Step 4: Patient Details**
- Enter a new patient name, email, phone
- OR click an existing patient's email to trigger identity verification
- Show the 4-digit phone verification for returning patients
- Click next

**Step 5: Confirmation**
- Show the confirmation with appointment details
- Highlight the intake form URL (public token-based)
- Highlight the consent form URL (public token-based)
- Highlight the Google Calendar link

**Booking Complete**: Show confirmation message and links

### 2. Admin Dashboard (1.5 minutes)

Login as `demo@serenity.test / password`

**Dashboard Home**
- Show "Today's Appointments" widget (5 appointments today)
- Show "This Week's Revenue" widget (revenue from completed appointments)
- Show "Active Patients" widget (unique patients)
- Show "This Month's Appointments" widget (count)

**Appointments Resource**
- Show list of all 40 appointments
- Filter by status (completed, scheduled, etc.)
- Show breakdown: 31 completed, 7 scheduled, 2 in progress

**Patients Resource**
- Show all 15 patients
- Click on a patient to see their appointment history

**Encounters Resource**
- Show list of 31 clinical encounters
- Click on one to see:
  - SOAP notes (Subjective, Objective, Assessment, Plan)
  - Associated AcupunctureEncounter with points, diagnosis, needle count
  - Associated appointment details

**Checkout Sessions Resource**
- Show list of 31 checkout sessions
- Filter by state (paid, open, payment_due)
- Show all are in "paid" state
- Click on one to see line items and payment details

### 3. Activity Log (1.5 minutes)

Navigate to "Security → Audit Log" (super-admin access)

- Show append-only activity log entries
- Filter by action type (created, updated, viewed, state_changed, signed)
- Show filtering by resource type (Patient, Appointment, Encounter, CheckoutSession)
- Show date range filtering
- Highlight that sensitive fields are filtered (password, card data, etc.)
- Sort by most recent to see latest activities

---

## Key Demo Talking Points

### Scalability
- "We're using global scopes to ensure strict multi-tenant isolation. All queries automatically filter by practice_id."
- "30 appointments with 31 encounters and checkouts shows the system handles real-world data volume."

### Clinical Data
- "Each encounter includes realistic SOAP notes and TCM acupuncture point data."
- "The system captures needle count and specific meridian protocols for treatment documentation."

### Payment Flow
- "All completed appointments have paid checkout sessions showing the full payment lifecycle."
- "Checkout state machine (open → paid) demonstrates business logic enforcement."

### Security & Compliance
- "Audit logging captures every action with IP address and user agent for compliance."
- "Sensitive fields (passwords, card data) are automatically filtered from logs."
- "Row-level security would be enforced at the database layer in production."

### Public Forms
- "Patients can complete intake and consent forms via public links (no login)."
- "Token-based access prevents unauthorized access to clinical data."

---

## Resetting Demo Data

If you need to reset and start over:

```bash
# Clear all data
php artisan migrate:fresh

# Re-seed with demo data
php artisan db:seed --class=DemoSeeder
```

---

## Customization

To modify demo data (patients, appointment types, etc.), edit:
- `database/seeders/DemoSeeder.php`

Then re-run:
```bash
php artisan db:seed --class=DemoSeeder
```

The seeder uses `firstOrCreate()` to avoid duplicate data if run multiple times.
