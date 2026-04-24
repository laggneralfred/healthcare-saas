<?php

namespace App\Filament\Pages;

use App\Models\AISuggestion;
use App\Models\AIUsageLog;
use App\Models\Appointment;
use App\Models\MessageLog;
use App\Services\AI\AIService;
use App\Services\PracticeContext;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class CommunicationsDashboard extends Page
{
    protected string $view = 'filament.pages.communications-dashboard';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;
    protected static ?string $navigationLabel = 'Overview';
    protected static string|\UnitEnum|null $navigationGroup = 'Communications';
    protected static ?int $navigationSort = 0;
    protected static ?string $title = 'Communications Overview';

    public int $sentThisMonth    = 0;
    public int $deliveredCount   = 0;
    public int $failedCount      = 0;
    public int $optedOutCount    = 0;
    public float $deliveryRate   = 0.0;
    public ?int $selectedAppointmentId = null;
    public string $reminderReason = 'appointment reminder';
    public ?string $aiReminderDraft = null;

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
