<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\CheckoutSessions\CheckoutSessionResource;
use App\Filament\Resources\Encounters\EncounterResource;
use App\Filament\Resources\MedicalHistories\MedicalHistoryResource;
use App\Filament\Resources\Patients\PatientResource;
use App\Mail\BookingConfirmationMail;
use App\Models\Appointment;
use App\Models\CheckoutSession;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\States\Appointment\Checkout;
use App\Models\States\Appointment\Completed;
use App\Models\States\Appointment\InProgress;
use App\Models\States\Appointment\Scheduled;
use App\Models\States\CheckoutSession\Open;
use App\Services\PracticeContext;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;

class FrontDeskDashboard extends Page
{
    protected static ?string $slug = 'front-desk';

    protected static ?string $title = 'Front Desk Dashboard';

    protected static ?string $navigationLabel = 'Front Desk';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|\UnitEnum|null $navigationGroup = 'Schedule';

    protected static ?int $navigationSort = -2;

    protected string $view = 'filament.pages.front-desk-dashboard';

    public string $patientSearch = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageOperations() ?? false;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    public function getViewData(): array
    {
        $practice = $this->practice();
        $appointments = $practice ? $this->todayAppointments($practice) : new Collection;
        $intakeItems = $practice ? $this->intakeItems($practice) : new Collection;
        $checkoutItems = $practice ? $this->checkoutItems($practice) : new Collection;

        return [
            'practice' => $practice,
            'todayAppointments' => $appointments,
            'arrivalItems' => $appointments->filter(fn (Appointment $appointment): bool => $appointment->status instanceof InProgress)->values(),
            'intakeItems' => $intakeItems,
            'checkoutItems' => $checkoutItems,
            'patientResults' => $practice ? $this->patientResults($practice) : new Collection,
            'alerts' => $this->alerts($appointments, $intakeItems, $checkoutItems),
            'appointmentsUrl' => AppointmentResource::getUrl('index'),
            'patientsUrl' => PatientResource::getUrl('index'),
            'createPatientUrl' => PatientResource::getUrl('create'),
        ];
    }

    private function practice(): ?Practice
    {
        $practiceId = PracticeContext::currentPracticeId();

        return $practiceId ? Practice::query()->find($practiceId) : null;
    }

    private function todayAppointments(Practice $practice): Collection
    {
        [$startOfDay, $endOfDay] = $this->dayBounds($practice);

        return Appointment::withoutPracticeScope()
            ->with(['patient', 'practitioner.user', 'appointmentType', 'medicalHistory', 'consentRecord'])
            ->where('practice_id', $practice->id)
            ->whereBetween('start_datetime', [$startOfDay, $endOfDay])
            ->orderBy('start_datetime')
            ->get();
    }

    private function intakeItems(Practice $practice): Collection
    {
        [$startOfDay, $endOfDay] = $this->dayBounds($practice);

        return Appointment::withoutPracticeScope()
            ->with(['patient', 'medicalHistory', 'consentRecord'])
            ->where('practice_id', $practice->id)
            ->whereBetween('start_datetime', [$startOfDay, $endOfDay])
            ->where(function ($query): void {
                $query->whereDoesntHave('medicalHistory')
                    ->orWhereHas('medicalHistory', fn ($query) => $query->where('status', '!=', 'complete'))
                    ->orWhereDoesntHave('consentRecord')
                    ->orWhereHas('consentRecord', fn ($query) => $query->where('status', '!=', 'complete'));
            })
            ->orderBy('start_datetime')
            ->limit(10)
            ->get();
    }

    private function checkoutItems(Practice $practice): Collection
    {
        return CheckoutSession::withoutPracticeScope()
            ->with(['patient', 'practitioner.user', 'appointment', 'encounter'])
            ->where('practice_id', $practice->id)
            ->where('state', Open::$name)
            ->latest('created_at')
            ->get();
    }

    private function patientResults(Practice $practice): Collection
    {
        $search = trim($this->patientSearch);

        return Patient::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'ilike', "%{$search}%")
                        ->orWhere('first_name', 'ilike', "%{$search}%")
                        ->orWhere('last_name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%")
                        ->orWhere('phone', 'ilike', "%{$search}%");
                });
            })
            ->latest()
            ->limit(8)
            ->get();
    }

    private function alerts(Collection $appointments, Collection $intakeItems, Collection $checkoutItems): array
    {
        return [
            [
                'label' => 'Waiting now',
                'count' => $appointments->filter(fn (Appointment $appointment): bool => $appointment->status instanceof InProgress)->count(),
                'tone' => 'warning',
            ],
            [
                'label' => 'Missing intake/forms',
                'count' => $intakeItems->count(),
                'tone' => $intakeItems->isEmpty() ? 'success' : 'danger',
            ],
            [
                'label' => 'Ready for checkout',
                'count' => $checkoutItems->count(),
                'tone' => 'primary',
            ],
        ];
    }

    private function dayBounds(Practice $practice): array
    {
        $now = now($practice->timezone ?? config('app.timezone'));

        return [
            $now->copy()->startOfDay(),
            $now->copy()->endOfDay(),
        ];
    }

    public function appointmentUrl(Appointment $appointment): string
    {
        return AppointmentResource::getUrl('view', ['record' => $appointment]);
    }

    public function patientUrl(Patient $patient): string
    {
        return PatientResource::getUrl('view', ['record' => $patient]);
    }

    public function medicalHistoryUrl(Appointment $appointment): ?string
    {
        return $appointment->medicalHistory
            ? MedicalHistoryResource::getUrl('view', ['record' => $appointment->medicalHistory])
            : null;
    }

    public function checkoutUrl(CheckoutSession $checkout): string
    {
        return CheckoutSessionResource::getUrl('edit', ['record' => $checkout]);
    }

    public function canCheckIn(Appointment $appointment): bool
    {
        return $this->appointmentHasStatus($appointment, Scheduled::class, Scheduled::$name)
            && (auth()->user()?->can('update', $appointment) ?? false);
    }

    public function canMarkInProgress(Appointment $appointment): bool
    {
        return $this->canCheckIn($appointment);
    }

    public function canOpenCheckout(Appointment $appointment): bool
    {
        return $this->appointmentHasStatus($appointment, Completed::class, Completed::$name)
            && (auth()->user()?->can('update', $appointment) ?? false);
    }

    public function canResendIntakeLink(Appointment $appointment): bool
    {
        return filled($appointment->patient?->email)
            && $appointment->medicalHistory !== null
            && $appointment->consentRecord !== null
            && (auth()->user()?->can('update', $appointment) ?? false);
    }

    public function checkInAppointment(int $appointmentId): void
    {
        $appointment = $this->authorizedAppointment($appointmentId);

        if (! $appointment || ! $this->canCheckIn($appointment)) {
            $this->notifyUnavailableAction();

            return;
        }

        $appointment->status->transitionTo(InProgress::class);

        Notification::make()
            ->title('Visit started.')
            ->success()
            ->send();

        $this->redirectToEncounterEdit($appointment);
    }

    public function markInProgress(int $appointmentId): void
    {
        $appointment = $this->authorizedAppointment($appointmentId);

        if (! $appointment || ! $this->canMarkInProgress($appointment)) {
            $this->notifyUnavailableAction();

            return;
        }

        $appointment->status->transitionTo(InProgress::class);

        Notification::make()
            ->title('Visit started.')
            ->success()
            ->send();

        $this->redirectToEncounterEdit($appointment);
    }

    public function openCheckout(int $appointmentId): void
    {
        $appointment = $this->authorizedAppointment($appointmentId);

        if (! $appointment || ! $this->canOpenCheckout($appointment)) {
            $this->notifyUnavailableAction();

            return;
        }

        $appointment->status->transitionTo(Checkout::class);

        Notification::make()
            ->title('Appointment moved to checkout.')
            ->success()
            ->send();
    }

    public function collectPayment(int $checkoutSessionId): void
    {
        $practiceId = PracticeContext::currentPracticeId();

        if (! $practiceId) {
            $this->notifyUnavailableAction();

            return;
        }

        $checkout = CheckoutSession::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->where('state', Open::$name)
            ->find($checkoutSessionId);

        if (! $checkout) {
            $this->notifyUnavailableAction();

            return;
        }

        $this->redirect($this->checkoutUrl($checkout));
    }

    public function resendIntakeLink(int $appointmentId): void
    {
        $appointment = $this->authorizedAppointment($appointmentId);

        if (! $appointment || ! $this->canResendIntakeLink($appointment)) {
            $this->notifyUnavailableAction();

            return;
        }

        Mail::to($appointment->patient->email)->send(new BookingConfirmationMail(
            $appointment,
            $appointment->medicalHistory,
            $appointment->consentRecord,
        ));

        Notification::make()
            ->title('Intake link sent.')
            ->success()
            ->send();
    }

    private function authorizedAppointment(int $appointmentId): ?Appointment
    {
        $practiceId = PracticeContext::currentPracticeId();

        if (! $practiceId) {
            return null;
        }

        $appointment = Appointment::withoutPracticeScope()
            ->with(['patient', 'practice', 'practitioner.user', 'appointmentType', 'medicalHistory', 'consentRecord'])
            ->where('practice_id', $practiceId)
            ->find($appointmentId);

        if (! $appointment || (auth()->user()?->cannot('update', $appointment) ?? true)) {
            return null;
        }

        return $appointment;
    }

    private function notifyUnavailableAction(): void
    {
        Notification::make()
            ->title('This action is not available for that appointment.')
            ->danger()
            ->send();
    }

    private function redirectToEncounterEdit(Appointment $appointment): void
    {
        $encounter = $this->encounterForAppointment($appointment);

        $this->redirect(EncounterResource::getUrl('edit', ['record' => $encounter]));
    }

    private function encounterForAppointment(Appointment $appointment): Encounter
    {
        $encounter = $appointment->encounter()->first();

        if ($encounter) {
            return $encounter;
        }

        return $appointment->encounter()->create([
            'practice_id' => $appointment->practice_id,
            'patient_id' => $appointment->patient_id,
            'practitioner_id' => $appointment->practitioner_id,
            'status' => 'draft',
            'visit_date' => $appointment->start_datetime?->toDateString() ?? now()->toDateString(),
        ]);
    }

    private function appointmentHasStatus(Appointment $appointment, string $stateClass, string $stateName): bool
    {
        return $appointment->status instanceof $stateClass
            || (string) $appointment->status === $stateName;
    }
}
