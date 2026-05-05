<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Pages\SchedulePage;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Appointments\Pages\Concerns\ValidatesPractitionerSchedule;
use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\AppointmentType;
use App\Models\Practitioner;
use App\Services\Scheduling\AppointmentAvailabilityService;
use App\Services\Scheduling\PractitionerScheduleService;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Collection;

class CreateAppointment extends CreateRecord
{
    use ValidatesPractitionerSchedule;

    protected static string $resource = AppointmentResource::class;

    public ?AppointmentRequest $appointmentRequest = null;

    public function scheduleContext(): ?array
    {
        if (! $this->appointmentRequest) {
            return null;
        }

        $state = $this->formState();
        $practitionerId = $state['practitioner_id'] ?? request('practitioner_id');
        $appointmentTypeId = $state['appointment_type_id'] ?? request('appointment_type_id') ?? $this->appointmentRequest->appointment_type_id;

        if (! $practitionerId && ! $appointmentTypeId) {
            return null;
        }

        $practiceId = auth()->user()?->practice_id;
        $practice = auth()->user()?->practice;
        $practitioner = $practitionerId
            ? Practitioner::withoutPracticeScope()
                ->with('practice', 'user')
                ->where('practice_id', $practiceId)
                ->find($practitionerId)
            : null;
        $appointmentType = $appointmentTypeId
            ? AppointmentType::withoutPracticeScope()
                ->where('practice_id', $practiceId)
                ->find($appointmentTypeId)
            : null;

        if (! $practice || ($practitionerId && ! $practitioner)) {
            return null;
        }

        $timezone = $practice->timezone ?: config('app.timezone');
        $selectedDate = $this->contextDate($state['start_datetime'] ?? request('start_datetime'), $timezone);
        $dayStart = $selectedDate->copy()->startOfDay();
        $dayEnd = $selectedDate->copy()->endOfDay();
        $schedule = app(PractitionerScheduleService::class);
        $suggestionStart = $selectedDate->copy()->startOfDay()->max(now($timezone));
        $suggestionEnd = $suggestionStart->copy()->addDays(14)->endOfDay();
        $suggestedSlots = $appointmentType
            ? app(AppointmentAvailabilityService::class)
                ->availableSlots($practice, $appointmentType, $practitioner, $suggestionStart, $suggestionEnd)
                ->take(10)
                ->map(fn (array $slot): array => $slot + [
                    'use_url' => $this->suggestionUrl($slot),
                ])
            : collect();

        return [
            'practitioner' => $practitioner,
            'appointmentType' => $appointmentType,
            'date' => $selectedDate,
            'workingWindows' => $practitioner ? $schedule->workingWindowsForDate($practitioner, $selectedDate) : collect(),
            'timeBlocks' => $practitioner ? $schedule->blocksForRange($practitioner, $dayStart, $dayEnd) : collect(),
            'appointments' => $practitioner ? $this->appointmentsForContext($practiceId, (int) $practitionerId, $dayStart, $dayEnd) : collect(),
            'calendarUrl' => $practitioner ? $this->calendarContextUrl((int) $practitionerId, $selectedDate) : null,
            'suggestedSlots' => $suggestedSlots,
        ];
    }

    public function mount(): void
    {
        parent::mount();

        if ($appointmentRequestId = request('appointment_request_id')) {
            $this->appointmentRequest = AppointmentRequest::withoutPracticeScope()
                ->with(['appointmentType', 'practitioner.user'])
                ->where('practice_id', auth()->user()->practice_id)
                ->find($appointmentRequestId);

            abort_if(! $this->appointmentRequest, 404);
        }

        $fill = [];

        if ($patientId = request('patient_id')) {
            $fill['patient_id'] = $patientId;
        }

        if ($appointmentTypeId = request('appointment_type_id')) {
            $fill['appointment_type_id'] = $appointmentTypeId;
        }

        if ($practitionerId = request('practitioner_id')) {
            $fill['practitioner_id'] = $practitionerId;
        }

        if ($startDatetime = request('start_datetime')) {
            $start                  = \Carbon\Carbon::parse($startDatetime);
            $fill['start_datetime'] = $start->format('Y-m-d H:i:00');
            $duration               = $this->durationForPrefill($fill['appointment_type_id'] ?? request('appointment_type_id'));
            $fill['duration_minutes'] = $duration;
            $fill['end_datetime']   = $start->copy()->addMinutes((int) $duration)->format('Y-m-d H:i:00');
        }

        if ($fill) {
            $this->form->fill($fill);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->validatePractitionerSchedule($data);

        $data['practice_id'] = auth()->user()->practice_id;
        $data['status']      = 'scheduled';
        unset($data['duration_minutes']);
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return request('return_url') ?: SchedulePage::getUrl();
    }

    private function formState(): array
    {
        return property_exists($this, 'data') && is_array($this->data) ? $this->data : [];
    }

    private function contextDate(mixed $value, string $timezone): Carbon
    {
        if ($value instanceof Carbon) {
            return $value->copy()->timezone($timezone);
        }

        if (filled($value)) {
            return Carbon::parse($value, $timezone);
        }

        return now($timezone);
    }

    private function appointmentsForContext(int $practiceId, int $practitionerId, Carbon $dayStart, Carbon $dayEnd): Collection
    {
        return Appointment::withoutPracticeScope()
            ->with(['patient', 'appointmentType'])
            ->where('practice_id', $practiceId)
            ->where('practitioner_id', $practitionerId)
            ->whereNotIn('status', ['cancelled'])
            ->where('start_datetime', '<=', $dayEnd->format('Y-m-d H:i:s'))
            ->where('end_datetime', '>=', $dayStart->format('Y-m-d H:i:s'))
            ->orderBy('start_datetime')
            ->get();
    }

    private function calendarContextUrl(int $practitionerId, Carbon $date): string
    {
        return SchedulePage::getUrl(array_filter([
            'date' => $date->format('Y-m-d'),
            'patient_id' => request('patient_id'),
            'appointment_request_id' => request('appointment_request_id'),
            'appointment_type_id' => request('appointment_type_id'),
            'practitioner_id' => $practitionerId,
            'return_url' => request('return_url'),
        ], fn ($value) => filled($value)));
    }

    private function suggestionUrl(array $slot): string
    {
        return AppointmentResource::getUrl('create', array_filter([
            'patient_id' => request('patient_id') ?: $this->appointmentRequest?->patient_id,
            'appointment_request_id' => request('appointment_request_id') ?: $this->appointmentRequest?->id,
            'appointment_type_id' => $slot['appointment_type_id'],
            'practitioner_id' => $slot['practitioner_id'],
            'start_datetime' => $slot['starts_at']->format('Y-m-d H:i:s'),
            'return_url' => request('return_url'),
        ], fn ($value) => filled($value)));
    }

    private function durationForPrefill(mixed $appointmentTypeId): int
    {
        $practice = auth()->user()?->practice;
        $appointmentType = $appointmentTypeId
            ? AppointmentType::withoutPracticeScope()
                ->where('practice_id', auth()->user()->practice_id)
                ->find($appointmentTypeId)
            : null;

        return (int) ($appointmentType?->duration_minutes ?: $practice?->default_appointment_duration ?: 60);
    }
}
