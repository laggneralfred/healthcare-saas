# Local Follow-Up Test Data

Use this local-only seed data to demo and manually test the relationship-centered Follow-Up workflow.

Do not run this against production.

## Run The Seeder

Recommended:

```bash
php artisan demo:seed-follow-up-workflow
```

If your local app runs on a different URL:

```bash
php artisan demo:seed-follow-up-workflow --base-url=http://127.0.0.1:8002
```

Direct seeder command:

```bash
php artisan db:seed --class=LocalFollowUpWorkflowSeeder
```

The dedicated command is better for manual testing because it prints usable appointment request links using the provided base URL.

## Login

Practice:

- `Local Follow-Up Test Practice`

Admin login:

- Email: `followup-admin@practiq.local`
- Password: `password`

Practitioner user:

- Email: `followup-practitioner@practiq.local`
- Password: `password`

## Test Email

All seeded patients use:

- `laggneralfred@gmail.com`

Exception:

- `No Email Test Patient` has no email address so the missing-email Invite Back state can be tested.

## Seeded Patients

| Patient | Preferred Language | Expected Care Status | Main Test Use |
| --- | --- | --- | --- |
| New Patient | English | New | Care Status baseline |
| Active Future Appointment Patient | English | Active | Future appointment and Schedule badges |
| Active Recent Visit Patient | English | Active | Recent completed visit |
| Needs Follow-Up Patient | English | Needs Follow-Up | Follow-Up inclusion |
| Cooling Patient | English | Cooling | Follow-Up inclusion |
| Inactive Patient | English | Inactive | Follow-Up inclusion |
| At Risk Cancelled Patient | English | At Risk | Recent cancelled appointment |
| At Risk No-Show Patient | English | At Risk | Recent no-show appointment |
| English Followup Patient | English | Needs Follow-Up | Invite Back, email, request links |
| Spanish Followup Patient | Spanish | Needs Follow-Up | Deterministic Spanish Invite Back |
| Chinese Translation Patient | Chinese | Cooling | AI translation preview |
| Vietnamese Translation Patient | Vietnamese | Cooling | AI translation preview |
| French Translation Patient | French | Cooling | AI translation preview |
| German Translation Patient | German | Cooling | AI translation preview |
| Other Language Patient | Other | Cooling | AI translation preview |
| No Email Test Patient | English | Needs Follow-Up | Missing-email helper |
| Opted Out Test Patient | English | Needs Follow-Up | Opt-out helper |
| Mobile Visit Note Patient | English | Active | Mobile visit note and dictation helper |
| Checkout Service Fee Patient | English | Active | Ready-for-checkout service fee line |
| Five Element Fee Patient | English | Active | Five Element appointment type and service fee |

## Service Fees And Checkout

The local test practice includes an active service fee catalog:

| Service Fee | Amount |
| --- | ---: |
| Initial Acupuncture Visit | $125.00 |
| Follow-Up Acupuncture Visit | $95.00 |
| Five Element Acupuncture Treatment | $110.00 |
| Herbal Consultation | $65.00 |
| Moxa / Adjunctive Treatment | $45.00 |
| Cupping Add-on | $35.00 |
| Wellness Consultation | $85.00 |

Common appointment types are linked to default service fees:

- `Initial Acupuncture Visit` -> `Initial Acupuncture Visit`
- `Follow-Up Acupuncture Visit` -> `Follow-Up Acupuncture Visit`
- `Five Element Acupuncture Treatment` -> `Five Element Acupuncture Treatment`
- `Herbal Consultation` -> `Herbal Consultation`
- `Wellness Consultation` -> `Wellness Consultation`
- `Local Follow-Up Visit` -> `Follow-Up Acupuncture Visit`

Checkout supports service fee line items. When staff proceed from an appointment-linked visit note to checkout, Practiq auto-suggests the appointment type's default service fee as one editable service line if the checkout has no existing line items. Staff can edit or remove that suggested line. Staff can also add a line item with `Line Type` = `Service` and choose one of the seeded service fees; the amount fills from the service catalog and remains editable.

For immediate checkout testing, the seeder also creates open checkout sessions with service lines:

- `Checkout Service Fee Patient`: Follow-Up Acupuncture Visit, $95.00
- `Five Element Fee Patient`: Five Element Acupuncture Treatment, $110.00

Open Today and use the Ready for Checkout section, or open Checkout directly, to verify that service line items and totals are present.

## Appointment Requests

The seeder creates Today dashboard request states:

- Pending: `English Followup Patient`
- Pending: `Spanish Followup Patient`
- Contacted: `Chinese Translation Patient`
- Scheduled: `French Translation Patient`
- Dismissed: `German Translation Patient`

Today should show only the pending English and Spanish requests.

The command also prints fresh public appointment request links for:

- `English Followup Patient`
- `Spanish Followup Patient`

Open those links in a private/logged-out browser to test the patient-facing form. The public form should not show patient records or availability.

## Manual QA Flow

1. Log in as `followup-admin@practiq.local`.
2. Open Follow-Up.
3. Confirm Needs Follow-Up, Cooling, Inactive, and At Risk patients appear.
4. Open Invite Back for `Spanish Followup Patient`.
5. Confirm the deterministic Spanish draft appears and AI translation is not required.
6. Open Invite Back for `Chinese Translation Patient`.
7. Confirm **Translate for Patient** appears.
8. Open Invite Back for `No Email Test Patient`.
9. Confirm Send Email is unavailable and the missing-email helper appears.
10. Open Invite Back for `Opted Out Test Patient`.
11. Confirm Send Email is unavailable and the opt-out helper appears.
12. Send Invite Back to `English Followup Patient` with the request link enabled.
13. Check `laggneralfred@gmail.com`.
14. Open the email's request link and submit preferred times.
15. Open Today.
16. Confirm the pending appointment request appears.
17. Click **Create Appointment** and confirm the patient is preselected.
18. Return to Today and test **Mark Contacted**, **Mark Scheduled**, and **Dismiss**.
19. Open Schedule and confirm appointment cards show care status and language context.
20. Open the seeded draft visit for `Mobile Visit Note Patient` and confirm the dictation helper is visible.
21. Open Today and confirm `Checkout Service Fee Patient` appears in Ready for Checkout.
22. Open that checkout and confirm the `Follow-Up Acupuncture Visit` service line is $95.00.
23. Open the `Five Element Fee Patient` checkout and confirm the `Five Element Acupuncture Treatment` service line is $110.00.
24. From an appointment-linked visit whose appointment type has a default service fee, click **Send to Checkout** and confirm the default service appears as an editable service line.
25. Start a new checkout line manually, choose `Service`, and confirm the seeded service fee list is available.

## Safety Notes

- This is fake local data only.
- The seeder refuses to run in production.
- The seeder recreates records only inside `Local Follow-Up Test Practice`.
- Public request links store only token hashes in the database.
- Staff still manually schedules appointments.
- No SMS is seeded or sent.
