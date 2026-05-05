<?php

namespace App\Filament\Resources\Patients\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Encounters\EncounterResource;
use App\Filament\Resources\MedicalHistories\MedicalHistoryResource;
use App\Filament\Resources\Patients\PatientResource;
use App\Mail\ExistingPatientFormMail;
use App\Mail\PatientPortalMagicLinkMail;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\MessageLog;
use App\Models\Patient;
use App\Models\States\Appointment\Cancelled as AppointmentCancelled;
use App\Models\States\CheckoutSession\Paid;
use App\Services\PatientCareStatusService;
use App\Services\PatientPortalTokenService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class ViewPatient extends ViewRecord
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => static::getResource()::getUrl('index'))
                ->color('gray'),

            Action::make('edit')
                ->label('Edit Patient Information')
                ->icon('heroicon-o-pencil')
                ->url(fn () => static::getResource()::getUrl('edit', ['record' => $this->record]))
                ->color('primary'),

            Action::make('intake_form')
                ->label('Patient Intake Form')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('warning')
                ->url(fn () => $this->record->medicalHistory
                    ? MedicalHistoryResource::getUrl('edit', ['record' => $this->record->medicalHistory->id])
                    : MedicalHistoryResource::getUrl('create', ['patient_id' => $this->record->id])),

            Action::make('new_encounter')
                ->label('New Visit')
                ->icon('heroicon-o-document')
                ->url(fn () => EncounterResource::getUrl('create', ['patient_id' => $this->record->id]))
                ->color('primary'),

            Action::make('new_appointment')
                ->label('New Appointment')
                ->icon('heroicon-o-calendar')
                ->url(fn () => AppointmentResource::getUrl('create', ['patient_id' => $this->record->id]))
                ->color('success'),

            Action::make('send_portal_link')
                ->label('Send Portal Link')
                ->icon('heroicon-o-key')
                ->color('info')
                ->visible(fn () => filled($this->record->email))
                ->requiresConfirmation()
                ->modalHeading('Send patient portal link?')
                ->modalDescription(fn () => 'This sends a secure magic link to '.$this->record->email.'.')
                ->action(fn () => $this->sendPortalLink()),

            Action::make('send_forms')
                ->label('Send Forms')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->visible(fn () => filled($this->record->email))
                ->requiresConfirmation()
                ->modalHeading('Send forms?')
                ->modalDescription(fn () => 'This sends a secure form link to '.$this->record->email.'. Submitted forms will wait for staff review.')
                ->action(fn () => $this->sendForms()),

            Action::make('mark_form_reviewed')
                ->label('Mark Form Reviewed')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->latestSubmittedForm() !== null)
                ->action(fn () => $this->markLatestFormReviewed()),

            Action::make('archive_form')
                ->label('Archive Form')
                ->icon('heroicon-o-archive-box')
                ->color('gray')
                ->visible(fn () => $this->latestFormSubmission() !== null)
                ->requiresConfirmation()
                ->action(fn () => $this->archiveLatestForm()),
        ];
    }

    public function sendPortalLink(): void
    {
        if (! filled($this->record->email)) {
            Notification::make()
                ->title('Add an email address before sending a portal link.')
                ->danger()
                ->send();

            return;
        }

        [$portalToken, $plainToken] = app(PatientPortalTokenService::class)
            ->createForExistingPatient($this->record, auth()->user());

        $portalUrl = route('patient.magic-link', ['token' => $plainToken]);
        $subject = 'Your secure link for '.$this->record->practice->name;
        $body = "Hi {$this->record->first_name},\n\nHere is your secure link for {$this->record->practice->name}. This link opens a basic patient dashboard and expires on {$portalToken->expires_at->format('M j, Y')}.\n\nIf you did not request this, you can ignore this email.";

        $messageLog = MessageLog::withoutPracticeScope()->create([
            'practice_id' => $this->record->practice_id,
            'patient_id' => $this->record->id,
            'appointment_id' => null,
            'practitioner_id' => null,
            'message_template_id' => null,
            'channel' => 'email',
            'recipient' => $this->record->email,
            'subject' => $subject,
            'body' => $body,
            'status' => 'pending',
        ]);

        try {
            Mail::to($this->record->email)->send(new PatientPortalMagicLinkMail($messageLog, $portalUrl));
        } catch (\Throwable $exception) {
            $messageLog->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => $exception->getMessage(),
            ]);

            Notification::make()
                ->title('The portal link could not be sent.')
                ->danger()
                ->send();

            return;
        }

        $messageLog->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        Notification::make()
            ->title('Patient portal link sent.')
            ->success()
            ->send();
    }

    public function sendForms(): void
    {
        if (! filled($this->record->email)) {
            Notification::make()
                ->title('Add an email address before sending forms.')
                ->danger()
                ->send();

            return;
        }

        $formTemplate = FormTemplate::findOrCreateDefaultNewPatientIntake($this->record->practice_id);

        FormSubmission::withoutPracticeScope()->create([
            'practice_id' => $this->record->practice_id,
            'patient_id' => $this->record->id,
            'new_patient_interest_id' => null,
            'form_template_id' => $formTemplate->id,
            'status' => FormSubmission::STATUS_PENDING,
        ]);

        [$portalToken, $plainToken] = app(PatientPortalTokenService::class)
            ->createForExistingPatientForm($this->record, auth()->user());

        $formUrl = route('patient.magic-link', ['token' => $plainToken]);
        $subject = 'Forms from '.$this->record->practice->name;
        $body = "Please complete {$formTemplate->name} for {$this->record->practice->name}. This secure link expires on {$portalToken->expires_at->format('M j, Y')}.\n\nIf you did not expect this, you can ignore this email.";

        $messageLog = MessageLog::withoutPracticeScope()->create([
            'practice_id' => $this->record->practice_id,
            'patient_id' => $this->record->id,
            'appointment_id' => null,
            'practitioner_id' => null,
            'message_template_id' => null,
            'channel' => 'email',
            'recipient' => $this->record->email,
            'subject' => $subject,
            'body' => $body,
            'status' => 'pending',
        ]);

        try {
            Mail::to($this->record->email)->send(new ExistingPatientFormMail($this->record, $formTemplate, $messageLog, $formUrl));
        } catch (\Throwable $exception) {
            $messageLog->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => $exception->getMessage(),
            ]);

            Notification::make()
                ->title('The forms could not be sent.')
                ->danger()
                ->send();

            return;
        }

        $messageLog->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        Notification::make()
            ->title('Forms sent.')
            ->success()
            ->send();
    }

    public function markLatestFormReviewed(): void
    {
        $submission = $this->latestSubmittedForm();

        if (! $submission) {
            return;
        }

        $submission->update([
            'status' => FormSubmission::STATUS_REVIEWED,
            'reviewed_at' => now(),
            'reviewed_by_user_id' => auth()->id(),
        ]);
    }

    public function archiveLatestForm(): void
    {
        $submission = $this->latestFormSubmission();

        if (! $submission) {
            return;
        }

        $submission->update(['status' => FormSubmission::STATUS_ARCHIVED]);
    }

    protected function resolveRecord($key): Model
    {
        return Patient::with([
            'encounters' => fn ($q) => $q->with('practitioner.user')->orderByDesc('visit_date'),
            'appointments' => fn ($q) => $q->with('practitioner.user', 'appointmentType', 'encounter')->orderByDesc('start_datetime'),
            'medicalHistories' => fn ($q) => $q->where('status', 'complete')->latest(),
            'medicalHistory',
            'checkoutSessions' => fn ($q) => $q->latest(),
            'communications' => fn ($q) => $q->latest()->limit(5),
            'formSubmissions' => fn ($q) => $q->with('formTemplate')->latest()->limit(10),
            'consentRecords' => fn ($q) => $q->where('status', 'complete')->latest(),
            'practice',
        ])->findOrFail($key);
    }

    public function infolist(Schema $schema): Schema
    {
        $patient = $this->record;
        $latestIntake = $patient->medicalHistories->first();
        $encounters = $patient->encounters;
        $lastEncounter = $encounters->first();

        $nextAppointment = $patient->appointments
            ->filter(fn ($a) => $a->start_datetime->isFuture()
                && ! ($a->status instanceof AppointmentCancelled))
            ->sortBy('start_datetime')
            ->first();

        $pastAppointments = $patient->appointments
            ->filter(fn ($a) => $a->start_datetime->isPast())
            ->sortByDesc('start_datetime')
            ->take(10);

        $upcomingAppointments = $patient->appointments
            ->filter(fn ($a) => $a->start_datetime->isFuture()
                && ! ($a->status instanceof AppointmentCancelled))
            ->sortBy('start_datetime');

        $outstandingBalance = $patient->checkoutSessions
            ->filter(fn ($c) => ! ($c->state instanceof Paid))
            ->sum('amount_total');

        $hasCompletedIntake = $latestIntake !== null;
        $hasSignedConsent = $patient->consentRecords->isNotEmpty();
        $hasOutstandingPayment = $outstandingBalance > 0;

        $discipline = $patient->practice?->discipline;
        $careStatus = app(PatientCareStatusService::class)->forPatient($patient);

        return $schema->components([
            ViewEntry::make('overview')
                ->view('filament.resources.patients.view-patient')
                ->viewData([
                    'patient' => $patient,
                    'latestIntake' => $latestIntake,
                    'encounters' => $encounters,
                    'lastEncounter' => $lastEncounter,
                    'nextAppointment' => $nextAppointment,
                    'upcomingAppointments' => $upcomingAppointments,
                    'pastAppointments' => $pastAppointments,
                    'outstandingBalance' => $outstandingBalance,
                    'hasCompletedIntake' => $hasCompletedIntake,
                    'hasSignedConsent' => $hasSignedConsent,
                    'hasOutstandingPayment' => $hasOutstandingPayment,
                    'careStatus' => $careStatus,
                    'checkoutSessions' => $patient->checkoutSessions,
                    'communications' => $patient->communications,
                    'formSubmissions' => $patient->formSubmissions,
                    'discipline' => $discipline,
                ])
                ->columnSpanFull(),
        ]);
    }

    private function latestSubmittedForm(): ?FormSubmission
    {
        return $this->record->formSubmissions()
            ->where('status', FormSubmission::STATUS_SUBMITTED)
            ->latest()
            ->first();
    }

    private function latestFormSubmission(): ?FormSubmission
    {
        return $this->record->formSubmissions()
            ->latest()
            ->first();
    }
}
