<?php

namespace App\Filament\Pages;

use App\Mail\InviteBackMail;
use App\Models\AISuggestion;
use App\Models\AIUsageLog;
use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\MessageLog;
use App\Models\Patient;
use App\Models\PatientCommunication;
use App\Models\PatientCommunicationPreference;
use App\Services\AI\AIService;
use App\Services\FollowUpPatientQueryService;
use App\Services\PatientCareStatusService;
use App\Services\PatientMessageDraftService;
use App\Services\PracticeContext;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CommunicationsDashboard extends Page
{
    protected string $view = 'filament.pages.communications-dashboard';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;
    protected static ?string $navigationLabel = 'Follow-Up';
    protected static string|\UnitEnum|null $navigationGroup = 'Follow-Up';
    protected static ?int $navigationSort = 0;
    protected static ?string $title = 'Follow-Up';

    public int $sentThisMonth    = 0;
    public int $deliveredCount   = 0;
    public int $failedCount      = 0;
    public int $optedOutCount    = 0;
    public float $deliveryRate   = 0.0;
    public ?int $selectedAppointmentId = null;
    public string $reminderReason = 'appointment reminder';
    public ?string $aiReminderDraft = null;
    public string $targetLanguage = 'Spanish';
    public ?string $translatedReminderDraft = null;
    public ?int $inviteBackPatientId = null;
    public ?string $inviteBackTranslatedBody = null;
    public ?string $inviteBackTranslationError = null;
    public bool $includeInviteBackRequestLink = true;
    public string $followUpStatusFilter = 'all';

    public function mount(): void
    {
        $practiceId = PracticeContext::currentPracticeId();

        if (! $practiceId) {
            return;
        }

        $this->sentThisMonth  = MessageLog::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereIn('status', ['sent', 'delivered'])
            ->count();

        $this->deliveredCount = MessageLog::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('status', 'delivered')
            ->count();

        $this->failedCount = MessageLog::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('status', 'failed')
            ->count();

        $this->optedOutCount = MessageLog::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('status', 'opted_out')
            ->count();

        $this->deliveryRate = $this->sentThisMonth > 0
            ? round(($this->deliveredCount / $this->sentThisMonth) * 100, 1)
            : 0.0;
    }

    public function draftAIReminder(AIService $ai): void
    {
        $practiceId = PracticeContext::currentPracticeId();

        if (! $practiceId) {
            Notification::make()
                ->title('Select a practice before using AI.')
                ->danger()
                ->send();

            return;
        }

        $appointment = $this->selectedAppointmentId
            ? Appointment::withoutPracticeScope()
                ->with(['patient', 'practice'])
                ->where('practice_id', $practiceId)
                ->find($this->selectedAppointmentId)
            : null;

        $context = [
            'patient_first_name' => $appointment?->patient?->first_name,
            'practice_name' => $appointment?->practice?->name,
            'appointment_datetime' => $appointment?->start_datetime?->format('l, F j, Y \a\t g:i A'),
            'reminder_reason' => $this->reminderReason,
        ];

        $suggestion = AISuggestion::create([
            'practice_id' => $practiceId,
            'user_id' => auth()->id(),
            'patient_id' => $appointment?->patient_id,
            'appointment_id' => $appointment?->id,
            'feature' => 'reminder_draft',
            'original_text' => json_encode($context, JSON_PRETTY_PRINT),
            'status' => 'pending',
        ]);

        try {
            $draft = $ai->draftReminderMessage($context);

            $suggestion->update([
                'suggested_text' => $draft,
                'status' => 'pending',
            ]);

            AIUsageLog::create([
                'practice_id' => $practiceId,
                'user_id' => auth()->id(),
                'feature' => 'reminder_draft',
                'status' => 'success',
            ]);

            $this->aiReminderDraft = $draft;

            Notification::make()
                ->title('AI reminder draft ready.')
                ->success()
                ->send();
        } catch (Throwable $exception) {
            $suggestion->update([
                'status' => 'failed',
            ]);

            AIUsageLog::create([
                'practice_id' => $practiceId,
                'user_id' => auth()->id(),
                'feature' => 'reminder_draft',
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            Notification::make()
                ->title('AI reminder draft is unavailable.')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function translateReminderDraft(AIService $ai): void
    {
        $practiceId = PracticeContext::currentPracticeId();

        if (! $practiceId) {
            Notification::make()
                ->title('Select a practice before using AI.')
                ->danger()
                ->send();

            return;
        }

        $draft = trim((string) $this->aiReminderDraft);

        if ($draft === '') {
            Notification::make()
                ->title('Enter or draft a reminder message before translating.')
                ->danger()
                ->send();

            return;
        }

        $appointment = $this->selectedAppointmentId
            ? Appointment::withoutPracticeScope()
                ->with(['patient', 'practice'])
                ->where('practice_id', $practiceId)
                ->find($this->selectedAppointmentId)
            : null;

        $context = [
            'target_language' => $this->targetLanguage,
            'source_text' => $draft,
            'practice_name' => $appointment?->practice?->name,
            'patient_id' => $appointment?->patient_id,
            'appointment_id' => $appointment?->id,
        ];

        $suggestion = AISuggestion::create([
            'practice_id' => $practiceId,
            'user_id' => auth()->id(),
            'patient_id' => $appointment?->patient_id,
            'appointment_id' => $appointment?->id,
            'feature' => 'translation',
            'original_text' => json_encode($context, JSON_PRETTY_PRINT),
            'status' => 'pending',
        ]);

        try {
            $translation = $ai->translateText($draft, $this->targetLanguage, [
                'practice_name' => $appointment?->practice?->name,
                'communication_type' => 'patient reminder',
            ]);

            $suggestion->update([
                'suggested_text' => $translation,
                'status' => 'pending',
            ]);

            AIUsageLog::create([
                'practice_id' => $practiceId,
                'user_id' => auth()->id(),
                'feature' => 'translation',
                'status' => 'success',
            ]);

            $this->translatedReminderDraft = $translation;

            Notification::make()
                ->title('Translation ready.')
                ->success()
                ->send();
        } catch (Throwable $exception) {
            $suggestion->update([
                'status' => 'failed',
            ]);

            AIUsageLog::create([
                'practice_id' => $practiceId,
                'user_id' => auth()->id(),
                'feature' => 'translation',
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            Notification::make()
                ->title('Translation is unavailable.')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getTranslationLanguageOptions(): array
    {
        return [
            'Spanish' => 'Spanish',
            'German' => 'German',
            'French' => 'French',
            'Chinese' => 'Chinese',
            'Vietnamese' => 'Vietnamese',
        ];
    }

    public function getFollowUpPatients(): Collection
    {
        $practiceId = PracticeContext::currentPracticeId();

        if (! $practiceId) {
            return new Collection();
        }

        $careStatusService = app(PatientCareStatusService::class);
        $includedStatuses = [
            PatientCareStatusService::STATUS_AT_RISK,
            PatientCareStatusService::STATUS_NEEDS_FOLLOW_UP,
            PatientCareStatusService::STATUS_COOLING,
            PatientCareStatusService::STATUS_INACTIVE,
        ];

        $filter = $this->followUpStatusFilter;
        if ($filter !== 'all' && ! in_array($filter, $includedStatuses, true)) {
            $filter = 'all';
        }

        $statusSort = [
            PatientCareStatusService::STATUS_AT_RISK => 0,
            PatientCareStatusService::STATUS_NEEDS_FOLLOW_UP => 1,
            PatientCareStatusService::STATUS_COOLING => 2,
            PatientCareStatusService::STATUS_INACTIVE => 3,
        ];

        return app(FollowUpPatientQueryService::class)
            ->candidatesForPractice($practiceId)
            ->map(function (Patient $patient) use ($careStatusService): Patient {
                $patient->setAttribute('care_status_summary', $careStatusService->forPatient($patient));

                return $patient;
            })
            ->filter(function (Patient $patient) use ($filter, $includedStatuses): bool {
                $status = $patient->getAttribute('care_status_summary')['key'];

                if (! in_array($status, $includedStatuses, true)) {
                    return false;
                }

                return $filter === 'all' || $status === $filter;
            })
            ->sortBy([
                fn (Patient $patient): int => $statusSort[$patient->getAttribute('care_status_summary')['key']] ?? 99,
                fn (Patient $patient): string => $patient->last_name ?? '',
                fn (Patient $patient): string => $patient->first_name ?? '',
            ])
            ->values();
    }

    public function getFollowUpStatusFilterOptions(): array
    {
        return [
            'all' => 'All',
            PatientCareStatusService::STATUS_NEEDS_FOLLOW_UP => 'Needs Follow-Up',
            PatientCareStatusService::STATUS_COOLING => 'Cooling',
            PatientCareStatusService::STATUS_AT_RISK => 'At Risk',
            PatientCareStatusService::STATUS_INACTIVE => 'Inactive',
        ];
    }

    public function openInviteBackPreview(int $patientId): void
    {
        $practiceId = PracticeContext::currentPracticeId();

        $patient = $practiceId
            ? Patient::withoutPracticeScope()
                ->where('practice_id', $practiceId)
                ->find($patientId)
            : null;

        $this->inviteBackPatientId = $patient?->id;
        $this->inviteBackTranslatedBody = null;
        $this->inviteBackTranslationError = null;
        $this->includeInviteBackRequestLink = true;
    }

    public function closeInviteBackPreview(): void
    {
        $this->inviteBackPatientId = null;
        $this->inviteBackTranslatedBody = null;
        $this->inviteBackTranslationError = null;
        $this->includeInviteBackRequestLink = true;
    }

    public function getInviteBackPatient(): ?Patient
    {
        $practiceId = PracticeContext::currentPracticeId();

        if (! $practiceId || ! $this->inviteBackPatientId) {
            return null;
        }

        return Patient::withoutPracticeScope()
            ->with(['practice', 'communicationPreference'])
            ->where('practice_id', $practiceId)
            ->find($this->inviteBackPatientId);
    }

    public function getInviteBackDraftMessage(): string
    {
        return $this->getInviteBackDraft()['body'] ?? '';
    }

    public function getInviteBackDraft(): array
    {
        $patient = $this->getInviteBackPatient();

        if (! $patient) {
            return [];
        }

        return app(PatientMessageDraftService::class)->inviteBack(
            $patient,
            $patient->practice,
            context: [
                'sender_name' => $patient->practice?->name,
            ],
        );
    }

    public function canTranslateInviteBackDraft(): bool
    {
        $draft = $this->getInviteBackDraft();

        return ($draft['fallback_used'] ?? false) === true
            && ($draft['language_code'] ?? Patient::LANGUAGE_ENGLISH) !== Patient::LANGUAGE_ENGLISH;
    }

    public function translateInviteBackDraft(AIService $ai): void
    {
        $practiceId = PracticeContext::currentPracticeId();
        $patient = $this->getInviteBackPatient();
        $draft = $this->getInviteBackDraft();
        $this->inviteBackTranslationError = null;

        if (! $practiceId || ! $patient || $draft === [] || ! $this->canTranslateInviteBackDraft()) {
            Notification::make()
                ->title('Translation is not available for this draft.')
                ->danger()
                ->send();

            return;
        }

        $sourceText = trim((string) ($draft['english_body'] ?? $draft['body'] ?? ''));
        $targetLanguage = (string) ($draft['language_label'] ?? $patient->preferred_language_label);

        $suggestion = AISuggestion::create([
            'practice_id' => $practiceId,
            'user_id' => auth()->id(),
            'patient_id' => $patient->id,
            'feature' => 'invite_back_translation',
            'context_json' => [
                'target_language' => $targetLanguage,
                'language_code' => $draft['language_code'] ?? $patient->preferred_language,
                'communication_type' => 'invite back follow-up',
            ],
            'original_text' => $sourceText,
            'status' => 'pending',
        ]);

        try {
            $translation = $ai->translateText($sourceText, $targetLanguage, [
                'practice_name' => $patient->practice?->name,
                'communication_type' => 'invite back follow-up',
            ]);

            $suggestion->update([
                'suggested_text' => $translation,
                'status' => 'pending',
            ]);

            AIUsageLog::create([
                'practice_id' => $practiceId,
                'user_id' => auth()->id(),
                'feature' => 'invite_back_translation',
                'status' => 'success',
            ]);

            $this->inviteBackTranslatedBody = $translation;

            Notification::make()
                ->title('Translation preview ready.')
                ->success()
                ->send();
        } catch (Throwable $exception) {
            $suggestion->update([
                'status' => 'failed',
            ]);

            AIUsageLog::create([
                'practice_id' => $practiceId,
                'user_id' => auth()->id(),
                'feature' => 'invite_back_translation',
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            $this->inviteBackTranslationError = 'Translation is unavailable right now. You can still use the English draft.';

            Notification::make()
                ->title('Translation is unavailable.')
                ->body('You can still use the English draft.')
                ->danger()
                ->send();
        }
    }

    public function saveInviteBackDraft(): void
    {
        $patient = $this->getInviteBackPatient();
        $draft = $this->getInviteBackDraft();

        if (! $patient || $draft === []) {
            Notification::make()
                ->title('Choose a patient before saving a draft.')
                ->danger()
                ->send();

            return;
        }

        PatientCommunication::withoutPracticeScope()->create([
            'practice_id' => $patient->practice_id,
            'patient_id' => $patient->id,
            'type' => PatientCommunication::TYPE_INVITE_BACK,
            'channel' => PatientCommunication::CHANNEL_PREVIEW,
            'language' => $draft['language_code'] ?? $patient->preferred_language,
            'subject' => $draft['subject'] ?? null,
            'body' => $this->inviteBackTranslatedBody ?: ($draft['body'] ?? ''),
            'status' => PatientCommunication::STATUS_DRAFT,
            'created_by' => auth()->id(),
        ]);

        Notification::make()
            ->title('Follow-up draft saved.')
            ->success()
            ->send();
    }

    public function getInviteBackEmailAvailability(): array
    {
        $patient = $this->getInviteBackPatient();

        if (! $patient) {
            return [
                'can_send' => false,
                'helper' => null,
            ];
        }

        if (blank($patient->email)) {
            return [
                'can_send' => false,
                'helper' => 'Add an email address before sending this follow-up.',
            ];
        }

        $preference = PatientCommunicationPreference::withoutPracticeScope()
            ->where('practice_id', $patient->practice_id)
            ->where('patient_id', $patient->id)
            ->first();

        if ($preference && ! $preference->canReceiveEmail()) {
            return [
                'can_send' => false,
                'helper' => 'This patient has opted out of messages.',
            ];
        }

        return [
            'can_send' => true,
            'helper' => null,
        ];
    }

    public function canSendInviteBackEmail(): bool
    {
        return $this->getInviteBackEmailAvailability()['can_send'] === true;
    }

    public function sendInviteBackEmail(): void
    {
        $patient = $this->getInviteBackPatient();
        $draft = $this->getInviteBackDraft();
        $availability = $this->getInviteBackEmailAvailability();

        if (! $patient || $draft === [] || $availability['can_send'] !== true) {
            Notification::make()
                ->title($availability['helper'] ?? 'Invite-back email cannot be sent yet.')
                ->danger()
                ->send();

            return;
        }

        $subject = $draft['subject'] ?? 'Checking in';
        $body = $this->inviteBackTranslatedBody ?: ($draft['body'] ?? '');

        $communication = PatientCommunication::withoutPracticeScope()->create([
            'practice_id' => $patient->practice_id,
            'patient_id' => $patient->id,
            'type' => PatientCommunication::TYPE_INVITE_BACK,
            'channel' => PatientCommunication::CHANNEL_EMAIL,
            'language' => $draft['language_code'] ?? $patient->preferred_language,
            'subject' => $subject,
            'body' => $body,
            'status' => PatientCommunication::STATUS_DRAFT,
            'created_by' => auth()->id(),
        ]);

        $messageLog = MessageLog::withoutPracticeScope()->create([
            'practice_id' => $patient->practice_id,
            'patient_id' => $patient->id,
            'channel' => PatientCommunication::CHANNEL_EMAIL,
            'recipient' => $patient->email,
            'subject' => $subject,
            'body' => $body,
            'status' => 'pending',
        ]);

        $appointmentRequest = null;
        $requestUrl = null;

        if ($this->includeInviteBackRequestLink) {
            [$appointmentRequest, $token] = AppointmentRequest::createLinkFor($patient, $communication);
            $requestUrl = $appointmentRequest->publicUrl($token);
        }

        try {
            Mail::to($patient->email)->send(new InviteBackMail($messageLog, $requestUrl));

            $sentAt = now();

            $communication->update([
                'status' => PatientCommunication::STATUS_SENT,
                'sent_at' => $sentAt,
            ]);

            $messageLog->update([
                'status' => 'sent',
                'sent_at' => $sentAt,
            ]);

            Notification::make()
                ->title('Invite-back email sent.')
                ->success()
                ->send();
        } catch (Throwable $exception) {
            $communication->update([
                'status' => PatientCommunication::STATUS_FAILED,
            ]);

            $messageLog->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => $exception->getMessage(),
            ]);

            $appointmentRequest?->update([
                'status' => AppointmentRequest::STATUS_FAILED,
            ]);

            Log::error('Invite-back email failed', [
                'patient_id' => $patient->id,
                'practice_id' => $patient->practice_id,
                'error' => $exception->getMessage(),
            ]);

            Notification::make()
                ->title('The email could not be sent. Your draft is still available.')
                ->danger()
                ->send();
        }
    }

    public function getLastCompletedVisitDate(Patient $patient): ?string
    {
        $encounterDate = $patient->encounters
            ->where('status', 'complete')
            ->sortByDesc(fn ($encounter) => $encounter->completed_on ?? $encounter->visit_date)
            ->first()?->visit_date;

        $appointmentDate = $patient->appointments
            ->filter(fn ($appointment): bool => in_array((string) $appointment->status, ['completed', 'checkout', 'closed'], true)
                && $appointment->start_datetime?->isPast())
            ->sortByDesc('start_datetime')
            ->first()?->start_datetime;

        $date = collect([$encounterDate, $appointmentDate])
            ->filter()
            ->sortDesc()
            ->first();

        return $date ? $date->format('M j, Y') : null;
    }

    public function getNextAppointmentDate(Patient $patient): ?string
    {
        $appointment = $patient->appointments
            ->filter(fn ($appointment): bool => $appointment->start_datetime?->isFuture()
                && in_array((string) $appointment->status, ['scheduled', 'in_progress', 'confirmed'], true))
            ->sortBy('start_datetime')
            ->first();

        return $appointment?->start_datetime?->format('M j, Y g:i A');
    }

    public function getUpcomingAppointments(): Collection
    {
        $practiceId = PracticeContext::currentPracticeId();

        if (! $practiceId) {
            return collect();
        }

        return Appointment::withoutPracticeScope()
            ->with(['patient'])
            ->where('practice_id', $practiceId)
            ->where('start_datetime', '>=', now()->subDay())
            ->orderBy('start_datetime')
            ->limit(25)
            ->get();
    }

    public function getRecentLogs(): Collection
    {
        $practiceId = PracticeContext::currentPracticeId();

        if (! $practiceId) {
            return collect();
        }

        return MessageLog::withoutPracticeScope()
            ->with(['patient', 'messageTemplate', 'practitioner.user'])
            ->where('practice_id', $practiceId)
            ->latest()
            ->limit(20)
            ->get();
    }
}
