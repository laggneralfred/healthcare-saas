# Island Massage And Acupuncture Demo Seed

## Purpose

`demo:seed-island-massage-acupuncture` creates a realistic fake clinic for testing a mixed Five Element acupuncture and massage therapy practice.

The seeded clinic is:

- practice: `Island Massage and Acupuncture`
- practitioner/admin: `Maria Cook`
- default mode: live-like testing practice, not demo mode
- documentation mode: Simple Visit Note Mode by default

All seeded patients, histories, notes, checkouts, requests, and communication records are fake demo data.

## Safety

The seeder uses:

- marker: `ISLAND_MASSAGE_ACUPUNCTURE_SEED`
- patient prefix: `Island Demo -`
- template prefix: `Island Demo -`
- inventory SKU prefix: `ISLAND-`

`--reset-demo-data` removes and recreates only records clearly created by this seeder inside the Island practice. It should not delete unrelated server data.

Normal seeded patients use `laggneralfred@gmail.com` unless you pass `--patient-email`. The missing-email patient intentionally has no email. The opted-out patient intentionally blocks email sending.

## Commands

Local:

```bash
php artisan demo:seed-island-massage-acupuncture --base-url=http://127.0.0.1:8002 --reset-demo-data
```

Docker/server:

```bash
docker compose exec app php artisan demo:seed-island-massage-acupuncture --base-url=https://app.practiqapp.com --reset-demo-data
```

Optional flags:

```bash
php artisan demo:seed-island-massage-acupuncture \
  --admin-email=maria-demo@practiq.local \
  --patient-email=laggneralfred@gmail.com \
  --base-url=https://app.practiqapp.com \
  --reset-demo-data
```

Use `--demo-mode` only when you intentionally want read-only/demo safeguards. By default this clinic is seeded with `is_demo = false` so checkout/payment behavior can be tested more realistically.

## Login

- email: `maria-demo@practiq.local`
- password: `PractiqLocalTest!2026`
- practice: `Island Massage and Acupuncture`

The practice uses `practice_type = five_element_acupuncture` because the app has one practice-level type. Massage is represented through Maria's second practitioner record, massage appointment types, massage medical histories, and massage visit notes.

Practitioner records:

- `Maria Cook` — Five Element Acupuncture
- `Maria Cook` — Massage Therapy

## Service Fees

| Service Fee | Price |
| --- | ---: |
| Initial Five Element Consultation + Treatment | 150.00 |
| Five Element Follow-Up Treatment | 110.00 |
| Extended Five Element Treatment | 135.00 |
| Moxa / Adjunctive Treatment | 45.00 |
| Herbal or Lifestyle Consultation | 65.00 |
| Massage Therapy 30 min | 55.00 |
| Massage Therapy 60 min | 95.00 |
| Massage Therapy 75 min | 120.00 |
| Massage Therapy 90 min | 145.00 |
| Therapeutic Bodywork Follow-Up | 105.00 |

Appointment types link to `default_service_fee_id` where supported. `No Default Fee Island Demo Visit` intentionally has no default fee.

## Patient Coverage

The seeder creates 40 fake patients:

- 20 Five Element acupuncture patients
- 20 massage therapy patients
- English, Spanish, Chinese, Vietnamese, French, German, and Other preferred-language examples
- one missing-email patient: `Island Demo - No Email Massage Patient`
- one opted-out patient: `Island Demo - Opted Out Five Element Patient`

Care and workflow coverage includes:

- New
- Active with future appointment
- Active with recent visit
- Needs Follow-Up
- Cooling
- Inactive
- At Risk after cancellation
- At Risk after no-show
- Ready for checkout
- Partial payment
- Paid/closed
- Missing intake/consent
- Pending appointment request

## Five Element QA

Open Five Element demo visits and confirm:

- Simple Visit Note Mode shows natural notes by default
- Five Element pulse fields are populated
- `pulse_before_treatment`
- `pulse_after_treatment`
- `pulse_change_interpretation`
- Worsley/Classical terminology is preserved
- notes include CF, Officials, CSOE, AE, Husband-Wife, Entry-Exit, Akabane, moxa, Roman numeral channel references, and pulse movement examples

Example seeded pulse language:

```text
Pulses pre: K --, Sp --, Ht -, PC -; St ++, GB ++.
Pulses post: K +, Sp =, Ht =, PC =; St +, GB +. Overall more even.
```

## Massage QA

Open massage demo visits and confirm notes include:

- neck and shoulder tension
- low back stiffness
- desk posture strain
- stress-related holding
- range of motion
- pressure tolerance
- session focus areas
- hydration/stretching recommendations
- maintenance care plan

## Today And Checkout QA

Open Today and verify:

- today's Five Element appointments
- today's massage appointments
- in-progress visit
- missing intake/consent item
- pending appointment requests
- Ready for Checkout items

Checkout cases include:

- open checkout with Five Element service line
- open checkout with massage service line
- paid checkout
- partial payment checkout
- checkout with moxa add-on inventory
- no-default-fee checkout that allows manual charge entry

`Collect Payment` should open the exact checkout session edit page:

```text
/admin/checkout-sessions/{id}/edit
```

It should not create a new checkout session, open a generic checkout page, or open the wrong patient.

## Follow-Up QA

Open Follow-Up and verify both Five Element and massage patients appear across:

- Needs Follow-Up
- Cooling
- At Risk
- Inactive

Invite Back testing:

- Spanish patients use deterministic Spanish drafts
- Chinese, Vietnamese, French, German, and Other patients can test AI translation preview when configured
- missing-email patient blocks Send Email
- opted-out patient blocks Send Email
- Save Draft creates `patient_communications`

## Appointment Request QA

Seeded requests include:

- pending request for a Five Element patient
- pending request for a massage patient
- contacted request
- scheduled request
- dismissed request

Today should show only pending appointment requests. Use View Request, Create Appointment, Mark Contacted, Mark Scheduled, and Dismiss to test staff handling.

The command prints fresh appointment request URLs for local/server testing.

## Reminder Schedule

Seeded templates/rules cover:

- 48-hour appointment reminder
- 24-hour appointment reminder
- same-day reminder
- post-visit check-in 2 days after treatment
- follow-up invitation 21 days after last visit
- reactivation check-in 60 days after last visit
- no-show check-in
- cancelled-not-rescheduled check-in

Seeding does not send reminders. Dispatch/sending requires explicit job, queue, schedule, or command behavior.

## Troubleshooting

- If patients do not appear, confirm you are logged in as `maria-demo@practiq.local` and viewing `Island Massage and Acupuncture`.
- If checkout/payment actions are unexpectedly blocked, confirm whether the command was run with `--demo-mode`.
- If service fees or checkout cases are missing, rerun with `--reset-demo-data`.
- If request links point to the wrong host, rerun with the correct `--base-url`.
- If tests collide on migrations, run the sequential command `composer test:feature`.

## Developer Notes

Relevant files:

- command: `app/Console/Commands/SeedIslandMassageAcupunctureCommand.php`
- seeder: `database/seeders/IslandMassageAcupunctureSeeder.php`
- test: `tests/Feature/IslandMassageAcupunctureSeederTest.php`
- docs: `docs/island-massage-acupuncture-seed.md`

Future additions should stay marked with `ISLAND_MASSAGE_ACUPUNCTURE_SEED` or the existing Island prefixes so reset behavior remains safe.
