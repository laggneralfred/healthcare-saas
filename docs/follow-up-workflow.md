# Follow-Up Workflow

## Purpose

Practiq's follow-up workflow helps staff gently re-engage patients who may need attention, without turning the product into online booking or a patient portal.

The flow is:

1. Follow-Up Center identifies patients who may need attention.
2. Staff opens Invite Back for a patient.
3. Staff previews, saves, translates when appropriate, or explicitly sends the message.
4. The Invite Back email can include a secure appointment request link.
5. The patient submits preferred days or times from a simple public form.
6. Staff reviews the pending request on Today.
7. Staff manually creates and schedules the appointment.
8. Staff marks the request Contacted, Scheduled, or Dismissed to keep the queue clean.

This workflow is intentionally staff-controlled. Patients can raise their hand and share scheduling preferences, but staff still owns scheduling, confirmation, and appointment creation.

## Staff Workflow

1. Open **Follow-Up**.
2. Review patients listed by Care Status and Preferred Language.
3. Use filters when useful: All, Needs Follow-Up, Cooling, At Risk, or Inactive.
4. Click **Invite Back** for a patient.
5. Review the subject, message body, preferred language, and recipient email.
6. If the patient uses an unsupported non-English preferred language, optionally click **Translate for Patient** and review the translated preview.
7. Choose an explicit action:
   - **Save Draft** records the message in `patient_communications` and does not contact the patient.
   - **Send Email** sends the reviewed message, creates communication/delivery records, and can include a request link.
8. When sending, keep **Include appointment request link** checked if staff wants the patient to submit preferred times.
9. After the patient submits the public request form, open **Today**.
10. Review **Appointment Requests**.
11. Click **View Request** to open the patient record when more context is needed.
12. Click **Create Appointment** to open the normal appointment creation flow with the patient preselected.
13. Confirm availability and manually schedule the appointment.
14. Mark the request:
   - **Mark Contacted** after staff contacts the patient.
   - **Mark Scheduled** after staff schedules the appointment.
   - **Dismiss** when the request should leave the pending queue without scheduling.

## Patient Workflow

1. The patient receives an Invite Back email.
2. If staff included the link, the email shows **Request an appointment**.
3. The patient clicks the link and sees a simple public form.
4. The form asks only for preferred days or times and an optional note.
5. Submitting the form creates a pending request for staff.
6. Staff contacts the patient to schedule and confirm the appointment.

The public form does not show schedule availability, appointment history, clinical details, or patient profile data.

## Explicit Non-Goals

These are deliberate boundaries for the current workflow:

- No full patient portal.
- No patient login.
- No public availability.
- No online booking.
- No patient-created appointments.
- No auto-booking.
- No SMS.
- No automatic sending.
- No appointment lifecycle changes.
- No reminder job rewrite.
- No checkout, encounter, or visit-note behavior changes.

## Data And Models

Core services and tables:

- `PatientCareStatusService`: calculates Care Status from existing patient appointments and encounters. It does not persist a care status column.
- `FollowUpPatientQueryService`: finds likely Follow-Up candidates for the selected practice before final display status is calculated.
- `PatientMessageDraftService`: creates deterministic Invite Back drafts. English and Spanish are localized templates; unsupported non-English languages fall back to English.
- `PatientCommunicationPreference`: controls whether the patient can receive email and must be respected before sending.
- `patient_communications`: audit/activity table for Invite Back draft, sent, and failed communication records.
- `message_logs`: delivery/provider-state table for email sends.
- `appointment_requests`: stores secure request-link rows, public form submissions, preferred times, optional notes, and staff handling status.
- `InviteBackMail`: renders the Invite Back email.

Important communication records:

- `patient_communications.type = invite_back`
- `patient_communications.channel = preview` for saved drafts
- `patient_communications.channel = email` for sent or failed email attempts
- `patient_communications.status = draft`, `sent`, or `failed`
- `message_logs.status = sent` or `failed` for email delivery state

Appointment request statuses:

- `link_sent`: a secure request link was created for an Invite Back email.
- `pending`: the patient submitted preferred times and the request needs staff attention.
- `contacted`: staff contacted the patient.
- `scheduled`: staff manually scheduled the appointment.
- `dismissed`: staff intentionally removed the request from the pending queue.
- `failed`: the request link was associated with a failed email send.

## Safety Rules

- Never send automatically from preview, translation, or draft generation.
- Email sending must happen only through explicit **Send Email**.
- Respect missing-email and opt-out rules before showing or enabling Send Email.
- Use `PatientCommunicationPreference` for communication eligibility.
- Do not send SMS from this workflow.
- Do not create appointments from public request submission.
- Do not expose patient records through public token links.
- Token links must remain random, patient-specific, practice-scoped, and stored as hashes.
- Preserve practice scoping for Follow-Up lists, communication records, message logs, appointment requests, and Today actions.
- Preserve selected-practice behavior through `PracticeContext`.
- Translation preview is advisory and must be reviewed before use.
- `Create Appointment` may preselect `patient_id`, but the staff member must still complete and save the appointment form.

## Manual QA Checklist

Follow-Up Center:

- Open Follow-Up and confirm a patient needing attention appears.
- Confirm Care Status and Preferred Language are visible.
- Test filters for Needs Follow-Up, Cooling, At Risk, and Inactive.
- Confirm active patients with future appointments do not appear as follow-up candidates.

Invite Back modal:

- Open Invite Back and confirm the modal appears.
- Confirm the subject and message body are visible before any action.
- Confirm English patients use the deterministic English draft.
- Confirm Spanish patients use the deterministic Spanish draft.
- Confirm unsupported non-English patients show fallback English copy and **Translate for Patient**.
- Click **Save Draft** and confirm a `patient_communications` row is created.
- Confirm Save Draft does not create a `message_logs` row and does not send email.
- Confirm Send Email is unavailable when the patient has no email.
- Confirm Send Email is unavailable when the patient has opted out.
- Click **Send Email** for an eligible patient and confirm:
  - a `patient_communications` row is created with `channel = email` and `status = sent`
  - a `message_logs` row is created with `status = sent`
  - no SMS is sent

Appointment request link:

- Send Invite Back with **Include appointment request link** checked.
- Confirm an `appointment_requests` row starts as `link_sent`.
- Open the request link in a logged-out or private browser session.
- Confirm the public page does not show the patient's name, history, or schedule availability.
- Submit preferred days/times and an optional note.
- Confirm the same `appointment_requests` row becomes `pending` and stores `submitted_at`.

Today dashboard:

- Open Today and confirm the pending request appears under **Appointment Requests**.
- Confirm requests from another practice do not appear.
- Click **View Request** and confirm it opens the patient record.
- Click **Create Appointment** and confirm the appointment form opens with the patient preselected.
- Confirm no appointment was created until staff saves the appointment form.
- Click **Mark Contacted** and confirm the request leaves the pending list.
- Repeat with **Mark Scheduled**.
- Repeat with **Dismiss**.
- Confirm non-pending requests remain in the database for history.

Regression checks:

- Existing reminder sending still works independently.
- Existing appointment drag/drop/resize behavior is unchanged.
- Encounter, checkout, and visit-note flows are unchanged.
- Full feature suite remains green.

## Demo Script

Use this script for a short end-to-end demo.

1. Open **Follow-Up**.
   - "This page surfaces patients who may need a gentle check-in."
2. Point out Care Status and Preferred Language.
   - "The final displayed status comes from existing patient activity, not a manually maintained pipeline field."
3. Click **Invite Back**.
   - "Staff reviews the message before anything is sent."
4. Show **Save Draft**.
   - "Saving records the draft for audit/history. It does not contact the patient."
5. Show **Send Email** and the recipient email.
   - "Sending is explicit, respects email availability and opt-out, and can include a request link."
6. Leave **Include appointment request link** checked and send the email.
   - "The patient can request a time, but they cannot book directly."
7. Open the public request link.
   - "This form is intentionally simple and does not expose patient records or availability."
8. Submit preferred days or times.
   - "The submission becomes a pending request for staff."
9. Open **Today**.
   - "Pending appointment requests appear in the staff work queue."
10. Click **Create Appointment**.
    - "Practiq opens the normal appointment form with the patient preselected."
11. Return to Today and click **Mark Scheduled**.
    - "After staff manually schedules, the request leaves the pending queue but remains in history."

## Developer Handoff Notes

- Keep the Follow-Up workflow calm and explicit. Avoid CRM, pipeline, sales, or automation-heavy wording.
- Use `PatientCareStatusService` for displayed Care Status so logic does not drift across pages.
- Keep `patient_communications` for communication activity and `message_logs` for delivery/provider state.
- Keep `appointment_requests` request-only until a deliberate online booking design exists.
- When changing email behavior, verify missing-email and opt-out paths.
- When changing public request behavior, verify token security and practice scoping.
- When adding filters or reporting, prefer query-level narrowing first, then still calculate user-facing status through the care status service.

## Future Roadmap Ideas

- More deterministic Invite Back language templates.
- Better Follow-Up filtering and reporting.
- Email response/open/click tracking if provider support exists.
- Optional SMS later, only after communication preferences and consent rules are designed.
- Intake and consent token links later, using the same careful public-link pattern.
- Dedicated appointment request history and staff outcome notes.
- Reminder nudges for old pending appointment requests.
- Appointment request analytics for response and scheduling rates.
- Online booking only as a later, deliberate product decision with explicit scheduling rules, availability controls, and patient safety review.
