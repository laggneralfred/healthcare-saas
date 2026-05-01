# Realistic Practice Demo Seed

## Purpose

`demo:seed-practice-realistic` seeds a realistic full-practice demo dataset for testing Practiq workflows end to end.

Use it to test:

- scheduling and calendar state
- patients and preferred languages
- visit notes
- Five Element acupuncture
- TCM acupuncture
- massage therapy
- service fees and appointment type defaults
- checkout and payments
- reminder templates and rules
- Follow-Up Center
- appointment requests
- patient communication history and safeguards

All seeded patients, notes, histories, and clinical details are fake demo data.

## Safety Warning

This command targets the practice attached to the user passed with `--user`.

Use `--reset-demo-data` only when you intentionally want to remove and recreate records created by this demo seeder. It should not delete unrelated real records, but do not run it casually against a real production practice unless you understand the scope.

Email behavior:

- normal seeded demo patients use `laggneralfred@gmail.com`
- `No Email Demo Patient` intentionally has no email
- `Opted Out Demo Patient` intentionally blocks email sending through `PatientCommunicationPreference`

## Commands

Local:

```bash
php artisan demo:seed-practice-realistic --user=admin@healthcare.test --base-url=http://127.0.0.1:8002 --reset-demo-data
```

Docker/server:

```bash
docker compose exec app php artisan demo:seed-practice-realistic --user=admin@healthcare.test --base-url=https://app.practiqapp.com --reset-demo-data
```

Without reset:

```bash
php artisan demo:seed-practice-realistic --user=admin@healthcare.test --base-url=http://127.0.0.1:8002
```

Use `--reset-demo-data` when you want a clean regenerated demo dataset. Omit it when you want the command to refresh/create the marked demo dataset without signaling an intentional reset. Current seeder behavior still clears only its own marked demo records before recreating them, so repeated runs remain idempotent.

The command fails if the user does not exist or is not linked to a `practice_id`.

## Login And Practice

Target user:

- `admin@healthcare.test`

Target practice:

- the practice attached to `admin@healthcare.test`

Documentation mode is controlled in Practice Settings. To test both documentation flows, toggle `Documentation & Billing Mode`:

- Simple Visit Note Mode
- SOAP / Insurance Documentation Mode

The seeder uses one existing practice rather than creating separate simple/SOAP practices.

## Seed Marker And Idempotency

The seeder uses:

- marker: `REALISTIC_PRACTICE_DEMO_SEED`
- patient prefix: `Realistic Demo -`
- demo message template prefix: `Realistic Demo -`
- inventory SKU prefix: `DEMO-`

Records cleaned up by the seeder:

- patients with the `Realistic Demo -` prefix
- `No Email Demo Patient`
- `Opted Out Demo Patient`
- records tied to those demo patients
- demo message templates named `Realistic Demo - ...`
- communication rules tied to those demo templates
- demo inventory products with `DEMO-...` SKUs

Records that should be left alone:

- unrelated real patients
- unrelated appointments
- unrelated encounters
- unrelated checkouts
- unrelated message templates
- unrelated inventory products
- existing practice settings

## Patient / Status / Language Coverage

| Patient | Expected Care Status | Language | Email Behavior | Tests |
| --- | --- | --- | --- | --- |
| `Realistic Demo - New Patient` | New | English | `laggneralfred@gmail.com` | New patient, Today appointment |
| `Realistic Demo - Active Future Appointment Patient` | Active | English | `laggneralfred@gmail.com` | Future scheduled appointment |
| `Realistic Demo - Active Recent Visit Patient` | Active | English | `laggneralfred@gmail.com` | Recent completed visit |
| `Realistic Demo - Needs Follow-Up Patient` | Needs Follow-Up | English | `laggneralfred@gmail.com` | Follow-Up, Invite Back, appointment request |
| `Realistic Demo - Cooling Patient` | Cooling | Spanish | `laggneralfred@gmail.com` | Spanish deterministic Invite Back |
| `Realistic Demo - Inactive Patient` | Inactive | Chinese | `laggneralfred@gmail.com` | AI translation preview path |
| `Realistic Demo - At Risk Cancelled Patient` | At Risk | Vietnamese | `laggneralfred@gmail.com` | Cancelled-not-rescheduled workflow |
| `Realistic Demo - At Risk No-Show Patient` | At Risk | French | `laggneralfred@gmail.com` | No-show workflow |
| `Realistic Demo - German Translation Patient` | Follow-up candidate | German | `laggneralfred@gmail.com` | Unsupported-language translation preview |
| `Realistic Demo - Other Language Patient` | Follow-up candidate | Other | `laggneralfred@gmail.com` | Other language fallback/translation path |
| `Realistic Demo - Five Element Demo Patient` | Active / history | English | `laggneralfred@gmail.com` | Five Element notes, pulses, checkout |
| `Realistic Demo - TCM Demo Patient` | Active / history | English | `laggneralfred@gmail.com` | TCM notes and calendar |
| `Realistic Demo - Massage Demo Patient` | Active / history | English | `laggneralfred@gmail.com` | Massage notes and in-progress visit |
| `No Email Demo Patient` | Needs Follow-Up | English | no email | Missing-email Send Email block |
| `Opted Out Demo Patient` | Needs Follow-Up | English | opted out | Opt-out Send Email block |

## Discipline Coverage

### Five Element Acupuncture

Five Element demo records use Worsley/Classical style notes and acupuncture details.

Coverage includes:

- CF / Causative Factor
- Officials
- CSOE
- AE / Aggressive Energy
- Husband-Wife treatment
- Entry-Exit blocks
- moxa
- command/source/horary/tonification/sedation point references
- Roman numeral channel nomenclature
- `pulse_before_treatment`
- `pulse_after_treatment`
- `pulse_change_interpretation`

Pulse shorthand examples:

```text
Pulses pre: K --, Sp --, Ht -, PC -; St ++, GB ++.
Pulses post: K +, Sp =, Ht =, PC =; St +, GB +. Overall more even.
```

### TCM Acupuncture

TCM demo records use TCM-style language:

- qi stagnation
- spleen qi deficiency
- dampness where relevant
- liver qi constraint
- TCM-style tongue/pulse notes
- point prescriptions such as `LI4, LV3, GB20, GB21, ST36`

TCM demo notes intentionally do not use Worsley terminology.

### Massage Therapy

Massage demo records cover:

- neck and shoulder tension
- low back stiffness
- range of motion
- pressure tolerance
- hydration and stretching recommendations
- maintenance care

### Wellness

General wellness demo records cover:

- wellness consultation
- wellness follow-up
- direct/no-appointment visit note workflow

## Service Fees And Prices

Prices live in `service_fees`. Appointment types can link to a default fee through `appointment_types.default_service_fee_id`.

| Service Fee | Price |
| --- | ---: |
| Initial Acupuncture Consultation + Treatment | 145.00 |
| Follow-Up Acupuncture Treatment | 95.00 |
| Five Element Acupuncture Treatment | 110.00 |
| Extended Acupuncture Session | 135.00 |
| Herbal Consultation | 65.00 |
| Moxa / Adjunctive Treatment | 45.00 |
| Cupping Add-on | 35.00 |
| Massage Therapy 30 min | 55.00 |
| Massage Therapy 60 min | 95.00 |
| Massage Therapy 75 min | 120.00 |
| Massage Therapy 90 min | 145.00 |
| Therapeutic Bodywork Follow-Up | 105.00 |
| Wellness Consultation | 85.00 |
| Follow-Up Wellness Visit | 75.00 |

Checkout suggests default service lines when an appointment type has a default service fee and the checkout has no existing lines. Line items remain editable.

`No Default Fee Demo Visit` intentionally has no `default_service_fee_id`.

## Checkout Test Cases

Use Checkout and Today to test:

- open checkout with service line
- paid checkout
- partial checkout
- payment-due checkout
- checkout with product/herb add-on
- checkout with adjustment/discount
- appointment type with no default fee
- default service fee auto-suggestion after proceeding from an appointment-linked visit

Suggested checks:

1. Open Checkout and filter/sort recent demo records.
2. Confirm service lines have prices from `service_fees`.
3. Confirm product/herb add-on appears as an inventory line.
4. Confirm partial payment leaves a balance.
5. Confirm paid checkout is not shown as ready for checkout on Today.
6. Confirm no-default-fee checkout does not invent a service line.

## Reminder Schedule

Seeded message templates/rules cover:

- 48-hour appointment reminder
- 24-hour appointment reminder
- same-day reminder
- post-visit check-in 2 days after treatment
- follow-up invitation 21 days after last visit
- reactivation check-in 60 days after last visit
- no-show check-in
- cancelled-not-rescheduled check-in

Seeding does not send reminders. Sending/dispatch requires explicit command, queue, schedule, or job behavior.

## Follow-Up Workflow QA

1. Open Follow-Up.
2. Verify Needs Follow-Up, Cooling, At Risk, and Inactive patients appear.
3. Open Invite Back for `Realistic Demo - Needs Follow-Up Patient`.
4. Save Draft and confirm a `patient_communications` record is created.
5. Send Email where allowed and confirm `patient_communications` and `message_logs` records.
6. Open Invite Back for `No Email Demo Patient` and confirm Send Email is blocked.
7. Open Invite Back for `Opted Out Demo Patient` and confirm Send Email is blocked.
8. Open Invite Back for `Realistic Demo - Cooling Patient` and confirm Spanish deterministic template behavior.
9. Open Invite Back for Chinese, Vietnamese, French, German, or Other language patients and test AI translation preview when configured.
10. Confirm no email/SMS sends automatically from preview, translation, or Save Draft.

## Appointment Request QA

1. Open Invite Back for a follow-up candidate.
2. Send Invite Back with the request link enabled.
3. Click the request link from the email or from the command output.
4. Submit preferred days/times and an optional note.
5. Open Today and verify the pending request appears.
6. Use View Request.
7. Use Create Appointment and confirm the patient is preselected.
8. Mark Contacted and confirm it leaves the pending list.
9. Mark Scheduled and confirm it leaves the pending list.
10. Dismiss another request and confirm it leaves the pending list.

## Calendar And Today QA

Today should include:

- appointments today
- an in-progress visit
- a checkout-ready visit
- pending appointment requests
- ready-for-checkout items

Calendar should include:

- multiple appointment states
- acupuncture appointment types
- massage appointment types
- care status badges
- language badges for non-English patients

`confirmed` is not currently a first-class appointment state in this codebase, so it is not seeded.

## Visit Note QA

1. In Practice Settings, choose Simple Visit Note Mode.
2. Start or edit a visit and confirm natural note editing is prominent.
3. Confirm the mobile/dictation helper appears near the note field.
4. Switch to SOAP / Insurance Documentation Mode.
5. Confirm structured SOAP fields and insurance documentation tools appear.
6. Open a Five Element demo visit and confirm pulse fields:
   - `pulse_before_treatment`
   - `pulse_after_treatment`
   - `pulse_change_interpretation`
7. Use AI Improve Note for a Five Element note and confirm it preserves Worsley/Classical language.
8. Confirm AI does not convert Worsley notes into generic TCM terms.
9. Confirm Documentation Check remains neutral and does not add Five Element-specific guidance.

## Troubleshooting

- If seeded patients do not appear, confirm you are logged in as the expected user and viewing the practice attached to `admin@healthcare.test`.
- If Chrome freezes or dims after login, check for a password manager unsafe-password warning.
- If emails do not arrive, check mail config, queue status, and mail logs.
- If service fees are empty, rerun the seeder with `--reset-demo-data`.
- If tests collide or show duplicate/missing table migration errors, use `composer test:feature` instead of parallel tests.
- If request links point to the wrong host, rerun with the correct `--base-url`.

## Developer Notes

Relevant files:

- command: `app/Console/Commands/SeedRealisticPracticeDemoCommand.php`
- seeder: `database/seeders/RealisticPracticeDemoSeeder.php`
- test: `tests/Feature/RealisticPracticeDemoSeederTest.php`
- docs: `docs/realistic-practice-demo-seed.md`

Do not mix this seeder with production customer data. Future additions should remain clearly marked and idempotent, using the existing marker/prefix conventions.
