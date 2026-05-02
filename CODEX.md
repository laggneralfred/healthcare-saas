# Practiq Development Handoff

## Current Baseline

- Laravel + Filament healthcare SaaS.
- Practice-scoped multi-tenancy.
- Spatie Laravel Permission role/access foundation.
- Current workflow/access/checkout checkpoint commit:
  - `177613b Add practitioner-aware visits and modular checkout workflow`
- Current uncommitted work includes completed UX, patient language, care status, Follow-Up, draft, communication activity, calendar context, visit note mobile UX, Invite Back AI translation preview, Follow-Up query/filter foundations, and explicit Invite Back email sending.

## Recent Completed Slices

Workflow documentation:

- `docs/follow-up-workflow.md`
- Covers Follow-Up -> Invite Back -> Appointment Request -> Staff Handling.
- Includes staff steps, patient steps, non-goals, data/models, safety and tenancy notes, manual QA checklist, demo script, and roadmap ideas.
- `docs/local-follow-up-test-data.md`
- Local/demo data can be seeded with `php artisan demo:seed-follow-up-workflow`.
- The local seed command creates fake patients under `Local Follow-Up Test Practice` and refuses production.
- The local Follow-Up seed data includes service fees, linked appointment type default fees, and open checkout sessions for `Checkout Service Fee Patient` and `Five Element Fee Patient`.
- Checkout service fees live in `service_fees`; appointment types can reference `default_service_fee_id`; checkout supports selectable service line items. Appointment-linked encounter checkout now auto-suggests one editable default service line only when the checkout has no existing line items.
- Public landing page now presents Practiq as calm, relationship-centered software for small clinics.
- Public user guide route: `/user-instructions`.
- Five Element acupuncture AI improve-note context now includes Worsley-specific guidance, Roman numeral channel mappings, and treatment concept preservation rules.
- Five Element acupuncture visit notes now include concise pulse prompts (`Pulses pre`, `Pulses post`, `Pulse movement`) plus optional Worsley/Classical Five Element pulse text fields on acupuncture encounter details.
- Practice Settings now exposes `Documentation & Billing Mode`, backed by `practices.insurance_billing_enabled`: Simple Visit Note Mode is `false`, and SOAP / Insurance Documentation Mode is `true`.
- The existing export page is available from the Reports navigation as `Exports` at `/admin/export-data`.
- Root routing is host-aware: `practiqapp.com/` and local hosts show the public landing page, while `app.practiqapp.com/` sends guests to `/login` and authenticated users to `/admin/dashboard`. The app-host `/login` route hands off to the existing Filament login at `/admin/login`.
- Realistic fake/demo practice data can be seeded for the practice attached to `admin@healthcare.test` with `php artisan demo:seed-practice-realistic --user=admin@healthcare.test --base-url=https://app.practiqapp.com --reset-demo-data`. It uses marker `REALISTIC_PRACTICE_DEMO_SEED`; do not run it against real production data unless intentionally seeding a demo practice.
- Today's Ready for Checkout cards link `Collect Payment` directly to the specific checkout session edit page, so partial, open, and no-default-fee sessions all open the exact checkout staff selected.
- When testing seeded practices, check `practices.is_demo` or the Today `Demo Mode` notice before diagnosing checkout/payment/email differences; demo safeguards hide or block some write-style behavior by design.
- Island Massage and Acupuncture can be seeded as a live-like fake clinic with `php artisan demo:seed-island-massage-acupuncture --base-url=https://app.practiqapp.com --reset-demo-data`; login is `maria-demo@practiq.local` / `PractiqLocalTest!2026`, and the marker is `ISLAND_MASSAGE_ACUPUNCTURE_SEED`.
- Patient portal foundation now supports existing-patient magic links at `/patient/magic-link/{token}` using `patient_portal_tokens`; only token hashes are stored, and `/patient/dashboard` is a minimal portal session page with no clinical notes.
- New patient interest submissions live in `new_patient_interests` via `/new-patient`; they never automatically create `patients` records and must not reveal whether an email already belongs to an existing patient.
- Staff can send intake forms to a new patient interest. The form link uses `patient_portal_tokens` with purpose `new_patient_form`, stores only a token hash, and grants access only to assigned form submissions, never the patient dashboard.
- New patient form completions are stored in `form_submissions` against the `new_patient_interest_id`; completing forms does not create a `Patient` record.
- New Patient Interest conversion is explicit and staff-triggered from the interest view. Submitted forms never create Patients automatically; conversion links the interest and related `form_submissions` to the created Patient.
- Same-practice duplicate email conversion is blocked until a future merge/review flow exists. The same email in another practice does not block conversion.
- Existing patients can request appointments from the patient portal. These are `appointment_requests` only; staff must manually create and confirm appointments, and portal patients can see only their own request statuses.
- Existing patient forms reuse `form_templates` and `form_submissions`. Staff-sent form links use the patient portal, and submitted forms do not automatically overwrite Patient records.
- Portal UX polish added a shared patient-facing nav, clearer request/form helper copy, and explicit reminders that appointment requests are not bookings and submitted forms wait for staff review.
- Authenticated patient portal pages share `resources/views/patient/layout.blade.php` for the page shell, clinic name/nav area, and main content slot. Public new-patient interest and token form pages remain separate.
- Patient detail pages include a clear `Edit Patient Information` header action that routes to the normal Patient edit page while preserving portal actions.

### UX / Navigation Cleanup

The Filament navigation now uses task-based groups:

- Today
- Calendar
- Patients
- Visits
- Follow-Up
- Checkout
- Reports
- Settings

User-facing language was softened:

- "Visits" instead of "Encounters" where user-facing.
- "Follow-Up" instead of CRM language.
- "Today" for the daily command center.

Helper copy added:

- "Here is what needs your attention today."
- "Patients who may need a gentle follow-up will appear here."
- "Write naturally first. You can organize or improve the note later. Changes are saved when you click Save Note."

### Preferred Language Foundation

Patients now have `preferred_language`.

Migration:

- `database/migrations/2026_04_30_045208_add_preferred_language_to_patients_table.php`

Supported values:

- `en` English
- `es` Spanish
- `zh` Chinese
- `vi` Vietnamese
- `fr` French
- `de` German
- `other` Other

Implemented in:

- `app/Models/Patient.php`
- `app/Filament/Resources/Patients/Schemas/PatientForm.php`
- `app/Filament/Resources/Patients/Tables/PatientsTable.php`
- `resources/views/filament/resources/patients/view-patient.blade.php`

CSV import support was added for optional `preferred_language` mapping.

### Patient Care Status Foundation

Service:

- `app/Services/PatientCareStatusService.php`

Calculated statuses:

- `new`
- `active`
- `needs_follow_up`
- `cooling`
- `inactive`
- `at_risk`

Thresholds:

- recently seen: 30 days
- cooling: 45 days
- inactive: 90 days
- recent risk event: 14 days

The service uses existing appointments and encounters and does not persist status.

Displayed in:

- Patient list/table as `Care Status`
- Patient detail header near preferred language
- Schedule/calendar appointment context badges
- Follow-Up Center cards and filters

### Follow-Up Center MVP

The existing `CommunicationsDashboard` is reused as the Follow-Up page.

Files:

- `app/Filament/Pages/CommunicationsDashboard.php`
- `resources/views/filament/pages/communications-dashboard.blade.php`
- `app/Services/FollowUpPatientQueryService.php`

Included care statuses:

- `needs_follow_up`
- `cooling`
- `at_risk`
- `inactive`

The page shows:

- patient name
- Care Status badge
- Preferred Language badge
- last completed visit date
- next appointment date
- suggested helper text
- Invite Back action

Follow-Up candidate loading now uses `FollowUpPatientQueryService` instead of loading every practice patient first.

Query behavior:

- practice-scoped through `PracticeContext`
- excludes patients with future scheduled/in-progress/confirmed appointments
- includes likely candidates based on recent no-show/cancelled appointments, older completed encounters, or older completed appointments
- eager-loads appointments and encounters only for the candidate set

Final displayed status still comes from `PatientCareStatusService` to avoid duplicated user-facing status logic.

UI filters:

- All
- Needs Follow-Up
- Cooling
- At Risk
- Inactive

Language filtering is deferred; it can be layered onto the same query later.

### Patient Message Draft Foundation

Service:

- `app/Services/PatientMessageDraftService.php`

Initial message type:

- `invite_back`

The service returns structured draft data:

- `type`
- `language_code`
- `language_label`
- `subject`
- `body`
- `english_body`
- `localized_body`
- `is_localized`
- `fallback_used`

Supported deterministic templates:

- English
- Spanish

Unsupported non-English languages fall back to English with `fallback_used = true`.

No AI calls, sending, or communication logs are created by this service.

### Invite Back Preview / Email

Invite Back starts as preview-first and never sends automatically.

The Follow-Up modal shows:

- subject
- message body
- preferred language
- recipient email when available
- `Preview message` label above the read-only draft
- fallback notice when a translated deterministic draft is not available
- Save Draft
- Send Email only when email sending is allowed
- optional appointment request link checkbox for Send Email

Fallback notice:

- "A translated draft is not available yet, so this preview is shown in English."

Optional AI translation preview is available only when:

- the patient preferred language is not English
- no deterministic localized template exists
- the draft fell back to English

Translation behavior:

- action label: `Translate for Patient`
- uses `AIService::translateText()`
- feature tag: `invite_back_translation`
- logs through existing `AISuggestion` and `AIUsageLog` conventions
- shows translated preview in modal state
- does not send and does not save unless `Save Draft` is clicked
- failure keeps the English draft visible with a friendly error

English and Spanish deterministic Invite Back drafts do not call AI translation.

Email sending behavior:

- action label: `Send Email`
- requires an explicit click
- modal helper: "Review this message before sending."
- modal helper: "Saving a draft does not contact the patient."
- modal helper: "Sending will email the patient at {email}."
- uses Laravel Mail through `app/Mail/InviteBackMail.php`
- renders `resources/views/emails/invite-back.blade.php`
- reuses existing `message_logs` delivery/provider-state convention
- respects `PatientCommunicationPreference::canReceiveEmail()`
- does not send if the patient has no email
- does not send if the patient has opted out
- does not send SMS
- can include a patient-facing appointment request link

Missing email helper:

- "Add an email address before sending this follow-up."

Opt-out helper:

- "This patient has opted out of messages."

Successful Send Email behavior:

- creates a `patient_communications` record
- `type = invite_back`
- `channel = email`
- `status = sent`
- stores selected/preferred language, subject, body, created_by, and sent_at
- creates a matching `message_logs` row with `status = sent`
- shows notification: "Invite-back email sent."

Failed Send Email behavior:

- keeps the modal draft visible
- creates/updates `patient_communications` with `status = failed`
- creates/updates `message_logs` with `status = failed`, `failed_at`, and `failure_reason`
- shows notification: "The email could not be sent. Your draft is still available."

Follow-Up polish notes:

- Empty state now says: "No patients need follow-up right now."
- Patient card header wraps so the Invite Back action does not crowd patient names on narrow screens.
- Invite Back modal has a viewport max-height and scrolls internally on small screens.
- Modal action row wraps so Close, Translate for Patient, Save Draft, and Send Email remain readable.
- Send Email is visually distinct from Save Draft.

### Appointment Request Link Foundation

Invite Back email can include a lightweight patient-facing appointment request link.

This is request-only:

- no patient portal
- no patient login
- no schedule availability exposed
- no direct appointment creation
- staff still manually schedules appointments

Files:

- `database/migrations/2026_04_30_090000_create_appointment_requests_table.php`
- `app/Models/AppointmentRequest.php`
- `app/Livewire/Public/AppointmentRequestForm.php`
- `resources/views/livewire/public/appointment-request-form.blade.php`
- route: `appointment-request.show`

Security model:

- links use a random 64-character token
- only a SHA-256 token hash is stored
- request rows are practice-scoped and patient-specific
- public page does not require login and does not show patient record details

Send Email behavior:

- the modal defaults to including the appointment request link
- staff can uncheck `Include appointment request link`
- when included, sending Invite Back creates an `appointment_requests` row with `status = link_sent`
- failed email sends mark the request row `failed`
- the Invite Back email shows a `Request an appointment` button

Patient-facing form:

- headline: "We’re glad to hear from you."
- helper: "Let us know when you’d like to come in."
- patient submits preferred days/times and an optional note
- submission updates the row to `status = pending` and sets `submitted_at`

Staff visibility:

- pending appointment requests appear on the Today dashboard in an `Appointment Requests` section
- alert count includes pending appointment requests
- helper copy says: "These patients requested a follow-up. Review their preferences and schedule manually."
- staff can open the patient record and schedule manually

Staff handling workflow:

- statuses: `pending`, `contacted`, `scheduled`, `dismissed`
- Today shows only `pending` requests by default
- actions: `View Request`, `Create Appointment`, `Mark Contacted`, `Mark Scheduled`, `Dismiss`
- `Create Appointment` opens the existing appointment creation flow with `patient_id` prefilled
- no appointment is created automatically from a patient request
- `contacted`, `scheduled`, and `dismissed` requests leave the main pending list but remain in request history

### Patient Communication Activity Foundation

`message_logs` was inspected and not reused because it represents delivery/provider message state. A separate table was created for draft/activity records.

Migration:

- `database/migrations/2026_04_30_061000_create_patient_communications_table.php`

Model:

- `app/Models/PatientCommunication.php`

Table:

- `patient_communications`

Core fields:

- `practice_id`
- `patient_id`
- `appointment_id`
- `encounter_id`
- `type`
- `channel`
- `language`
- `subject`
- `body`
- `status`
- `created_by`
- `sent_at`

Constants include:

- type: `invite_back`
- statuses: `draft`, `previewed`, `marked_sent`, `sent`, `failed`
- channels: `preview`, `manual`, `email`, `sms`

Follow-Up modal now includes `Save Draft`.

Save Draft behavior:

- creates a `patient_communications` record
- `type = invite_back`
- `channel = preview`
- `status = draft`
- stores the translated preview body when one exists; otherwise stores the deterministic draft body
- does not send email/SMS
- does not create `message_logs`
- shows notification: "Follow-up draft saved."

Patient detail shows a subtle `Recent Follow-Up` section only when records exist.

### Schedule Care Context

The appointment/calendar event feed includes patient care context where a patient is available:

- `care_status_key`
- `care_status_label`
- `care_status_color`
- `care_status_helper`
- `preferred_language`
- `preferred_language_label`

Calendar rendering shows subtle Care Status and Preferred Language badges without changing drag/drop, resize, timezone, or appointment lifecycle behavior.

### Visit Note Mobile / Dictation UX

Simple Visit Note and SOAP/Insurance edit layouts stack more cleanly on phones.

The main note field includes unobtrusive helper copy:

- "Tip: On your phone, tap the microphone on your keyboard to dictate your note."

Save Note, AI Draft, Reset Template, Proceed to Checkout, appointment lifecycle, checkout flow, and AI behavior are preserved.

## Important Non-Goals Preserved

- No SMS sending added.
- No automatic email sending added.
- No automatic AI translation, sending, or saving added.
- No appointment lifecycle changes.
- No encounter/visit logic changes.
- No checkout logic changes.
- No reminder job logic changes.
- No persisted care status.
- No full CRM or pipeline behavior.
- No patient portal or direct appointment booking added.

## Tests

Most recent full feature suite result:

- `422 passed, 1598 assertions`

Most recent focused follow-up/status/draft result:

- `AppointmentRequestTest`: `3 passed, 12 assertions`
- `FollowUpCenterTest`: `15 passed, 119 assertions`
- `FrontDeskDashboardTest`: `17 passed, 91 assertions`
- `PatientCommunicationTest`: `1 passed, 5 assertions`
- `FilamentSmokeTest`: `7 passed, 62 assertions`
- `PatientCareStatusTest`: `9 passed, 14 assertions`
- `PatientMessageDraftServiceTest`: `5 passed, 26 assertions`
- `SendReminderJobTest`: `4 passed, 11 assertions`
- `DispatchRemindersJobTest`: `3 passed, 3 assertions`
- `MessageTemplateTest`: `4 passed, 12 assertions`
- `MultiTenancyTest`: `3 passed, 5 assertions`
- `ReminderDraftAITest`: `4 passed, 15 assertions`
- `ReminderTranslationAITest`: `4 passed, 17 assertions`

Diff checks:

- `git diff --check` passed.

Use the sequential feature-suite script for autonomous local feature test runs:

```bash
composer test:feature
```

The script runs:

```bash
DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5433 DB_DATABASE=healthcare_saas_test DB_USERNAME=healthcare DB_PASSWORD=secret php artisan test tests/Feature
```

Run migration-heavy feature tests sequentially unless isolated parallel test databases are explicitly configured. Do not use `--parallel` against the shared local PostgreSQL test schema; it can create duplicate-table or missing-table migration collisions. If a parallel collision happens, refresh the test database, then rerun sequentially:

```bash
DB_HOST=127.0.0.1 DB_PORT=5433 php artisan migrate:fresh --env=testing
composer test:feature
```

The sandbox may block direct PostgreSQL access; rerun with approval/escalation if the connection fails before assertions.

## Files To Include When Staging Current Work

Important new files:

- `CODEX.md`
- `app/Mail/InviteBackMail.php`
- `app/Livewire/Public/AppointmentRequestForm.php`
- `app/Models/AppointmentRequest.php`
- `app/Models/PatientCommunication.php`
- `app/Services/FollowUpPatientQueryService.php`
- `app/Services/PatientCareStatusService.php`
- `app/Services/PatientMessageDraftService.php`
- `database/migrations/2026_04_30_045208_add_preferred_language_to_patients_table.php`
- `database/migrations/2026_04_30_061000_create_patient_communications_table.php`
- `database/migrations/2026_04_30_090000_create_appointment_requests_table.php`
- `resources/views/emails/invite-back.blade.php`
- `resources/views/livewire/public/appointment-request-form.blade.php`
- `tests/Feature/AppointmentRequestTest.php`
- `tests/Feature/FollowUpCenterTest.php`
- `tests/Feature/PatientCareStatusTest.php`
- `tests/Feature/PatientCommunicationTest.php`
- `tests/Feature/PatientMessageDraftServiceTest.php`

Do not add unrelated local artifacts unless explicitly requested:

- `marketing/.codex`
- `docs/practitioner-review/`
- stray quoted-fragment filenames currently present in the worktree
