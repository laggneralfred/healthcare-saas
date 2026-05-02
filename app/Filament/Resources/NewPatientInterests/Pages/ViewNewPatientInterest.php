<?php

namespace App\Filament\Resources\NewPatientInterests\Pages;

use App\Filament\Resources\NewPatientInterests\NewPatientInterestResource;
use App\Filament\Resources\Patients\PatientResource;
use App\Mail\NewPatientIntakeFormMail;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\NewPatientInterest;
use App\Services\NewPatientInterestConversionService;
use App\Services\PatientPortalTokenService;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Mail;

class ViewNewPatientInterest extends ViewRecord
{
    protected static string $resource = NewPatientInterestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => static::getResource()::getUrl('index'))
                ->color('gray'),

            Action::make('mark_reviewing')
                ->label('Mark Reviewing')
                ->color('warning')
                ->visible(fn () => $this->record->status !== NewPatientInterest::STATUS_REVIEWING)
                ->action(function (): void {
                    $this->record->update(['status' => NewPatientInterest::STATUS_REVIEWING]);
                    $this->refreshFormData(['status']);
                }),

            Action::make('send_intake_forms')
                ->label('Send Intake Forms')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(fn () => filled($this->record->email))
                ->requiresConfirmation()
                ->modalHeading('Send intake forms?')
                ->modalDescription(fn () => 'This sends a secure form link to '.$this->record->email.'. It does not create a patient record.')
                ->action(fn () => $this->sendIntakeForms()),

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

            Action::make('create_patient')
                ->label('Create Patient')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->visible(fn () => $this->canConvertInterest())
                ->requiresConfirmation()
                ->modalHeading('Create patient record?')
                ->modalDescription(fn () => $this->conversionModalDescription())
                ->action(fn () => $this->createPatientFromInterest()),

            Action::make('open_converted_patient')
                ->label('Open Patient')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->visible(fn () => filled($this->record->converted_patient_id))
                ->url(fn () => PatientResource::getUrl('view', ['record' => $this->record->converted_patient_id])),

            Action::make('mark_declined')
                ->label('Decline')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update([
                        'status' => NewPatientInterest::STATUS_DECLINED,
                        'responded_at' => now(),
                        'responded_by_user_id' => auth()->id(),
                    ]);
                    $this->refreshFormData(['status', 'responded_at', 'responded_by_user_id']);
                }),

            Action::make('mark_closed')
                ->label('Close')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update([
                        'status' => NewPatientInterest::STATUS_CLOSED,
                        'responded_at' => now(),
                        'responded_by_user_id' => auth()->id(),
                    ]);
                    $this->refreshFormData(['status', 'responded_at', 'responded_by_user_id']);
                }),
        ];
    }

    public function sendIntakeForms(): void
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
            'new_patient_interest_id' => $this->record->id,
            'form_template_id' => $formTemplate->id,
            'status' => FormSubmission::STATUS_PENDING,
        ]);

        [$portalToken, $plainToken] = app(PatientPortalTokenService::class)
            ->createForNewPatientInterest($this->record, auth()->user());

        $formUrl = route('patient.new-patient-form.show', ['token' => $plainToken]);

        try {
            Mail::to($this->record->email)->send(new NewPatientIntakeFormMail($this->record, $formTemplate, $formUrl));
        } catch (\Throwable $exception) {
            Notification::make()
                ->title('The intake forms could not be sent.')
                ->body('The pending form was created, but the email did not send.')
                ->danger()
                ->send();

            return;
        }

        $this->record->update([
            'status' => NewPatientInterest::STATUS_FORMS_SENT,
            'responded_at' => now(),
            'responded_by_user_id' => auth()->id(),
        ]);

        $this->record->refresh();
        $this->refreshFormData(['status', 'responded_at', 'responded_by_user_id']);

        Notification::make()
            ->title('Intake forms sent.')
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

        $submission->update([
            'status' => FormSubmission::STATUS_ARCHIVED,
        ]);
    }

    public function createPatientFromInterest(): void
    {
        try {
            $patient = app(NewPatientInterestConversionService::class)
                ->convert($this->record, auth()->user());
        } catch (\Illuminate\Validation\ValidationException $exception) {
            Notification::make()
                ->title(collect($exception->errors())->flatten()->first() ?? 'This interest could not be converted.')
                ->danger()
                ->send();

            return;
        }

        $this->record->refresh();
        $this->refreshFormData(['status', 'responded_at', 'responded_by_user_id', 'converted_patient_id']);

        Notification::make()
            ->title('Patient created.')
            ->body('The interest and submitted forms are now linked to the patient record.')
            ->success()
            ->actions([
                \Filament\Actions\Action::make('open_patient')
                    ->label('Open Patient')
                    ->url(PatientResource::getUrl('view', ['record' => $patient->id])),
            ])
            ->send();
    }

    public function infolist(Schema $schema): Schema
    {
        $formSubmissions = $this->record->formSubmissions()
            ->with('formTemplate')
            ->latest()
            ->get();

        return $schema->components([
            TextEntry::make('full_name')
                ->label('Name'),

            TextEntry::make('email'),

            TextEntry::make('phone')
                ->placeholder('—'),

            TextEntry::make('preferred_service')
                ->label('Preferred Service')
                ->placeholder('—'),

            TextEntry::make('preferred_days_times')
                ->label('Preferred Days/Times')
                ->placeholder('—'),

            TextEntry::make('message')
                ->placeholder('—'),

            TextEntry::make('status')
                ->badge()
                ->formatStateUsing(fn ($state) => NewPatientInterest::STATUS_OPTIONS[$state] ?? str($state)->replace('_', ' ')->title()),

            TextEntry::make('created_at')
                ->label('Submitted')
                ->dateTime('M j, Y g:i A'),

            TextEntry::make('responded_at')
                ->label('Responded')
                ->dateTime('M j, Y g:i A')
                ->placeholder('—'),

            TextEntry::make('convertedPatient.full_name')
                ->label('Converted Patient')
                ->placeholder('Not converted')
                ->url(fn () => $this->record->converted_patient_id
                    ? PatientResource::getUrl('view', ['record' => $this->record->converted_patient_id])
                    : null),

            ViewEntry::make('form_submissions')
                ->label('Form Submissions')
                ->view('filament.resources.new-patient-interests.form-submissions')
                ->viewData(['formSubmissions' => $formSubmissions])
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

    private function canConvertInterest(): bool
    {
        return ! $this->record->converted_patient_id
            && ! in_array($this->record->status, [
                NewPatientInterest::STATUS_CONVERTED,
                NewPatientInterest::STATUS_DECLINED,
                NewPatientInterest::STATUS_CLOSED,
            ], true);
    }

    private function conversionModalDescription(): string
    {
        if ($this->record->formSubmissions()
            ->whereNotNull('submitted_data_json')
            ->whereIn('status', [
                FormSubmission::STATUS_SUBMITTED,
                FormSubmission::STATUS_REVIEWED,
                FormSubmission::STATUS_ACCEPTED,
            ])
            ->exists()) {
            return 'This creates a real patient record in this practice and links the submitted forms for history.';
        }

        return 'No submitted forms are attached yet. You can still create a patient from the request details, but review carefully first.';
    }
}
