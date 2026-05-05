<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Encounters\EncounterResource;
use App\Models\Appointment;
use App\Models\Practice;
use App\Services\PatientCareStatusService;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use App\Services\PracticeContext;
use Illuminate\Support\Carbon;

class AppointmentCalendarWidget extends Widget
{
    protected string $view = 'filament.widgets.appointment-calendar';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    protected function getViewData(): array
    {
        $practiceId = PracticeContext::currentPracticeId();
        $timezone = $practiceId
            ? Practice::find($practiceId)?->timezone ?? 'UTC'
            : 'UTC';
        $practitionerId = request()->integer('practitioner_id');
        $date = request('date');

        $todayAppointments = $practiceId
            ? $this->todayAppointments($practiceId, $timezone)
            : collect();

        $contextParams = array_filter([
            'patient_id' => request('patient_id'),
            'appointment_request_id' => request('appointment_request_id'),
            'appointment_type_id' => request('appointment_type_id'),
            'practitioner_id' => $practitionerId ?: null,
            'return_url' => request('return_url'),
        ], fn ($value) => filled($value));

        return [
            'eventsUrl'     => route('admin.calendar.events', array_filter([
                'practitioner_id' => $practitionerId ?: null,
            ])),
            'createBaseUrl' => AppointmentResource::getUrl('create') . (filled($contextParams) ? '?' . http_build_query($contextParams) : ''),
            'calendarTimezone' => $timezone,
            'todayAppointments' => $todayAppointments,
            'initialDate' => $date ?: null,
        ];
    }

    private function todayAppointments(int $practiceId, string $timezone)
    {
        $careStatusService = app(PatientCareStatusService::class);
        $startOfDay = now($timezone)->startOfDay()->utc();
        $endOfDay = now($timezone)->endOfDay()->utc();

        return Appointment::withoutPracticeScope()
            ->with([
                'patient.appointments',
                'patient.encounters',
                'practitioner.user',
                'appointmentType',
                'encounter',
            ])
            ->where('practice_id', $practiceId)
            ->whereBetween('start_datetime', [$startOfDay, $endOfDay])
            ->orderBy('start_datetime')
            ->get()
            ->each(function (Appointment $appointment) use ($careStatusService): void {
                if ($appointment->patient) {
                    $appointment->patient->setAttribute(
                        'care_status_summary',
                        $careStatusService->forPatient($appointment->patient)
                    );
                }
            });
    }

    public function appointmentUrl(Appointment $appointment): string
    {
        return AppointmentResource::getUrl('view', ['record' => $appointment]);
    }

    public function primaryActionUrl(Appointment $appointment): string
    {
        if ($appointment->encounter) {
            return EncounterResource::getUrl('view', ['record' => $appointment->encounter]);
        }

        if ($appointment->patient_id) {
            return EncounterResource::getUrl('create') . '?appointment_id=' . $appointment->id . '&patient_id=' . $appointment->patient_id;
        }

        return $this->appointmentUrl($appointment);
    }

    public function primaryActionLabel(Appointment $appointment): string
    {
        return $appointment->encounter ? 'View Visit' : 'Start Visit';
    }

    public function updateAppointmentTime(int $id, string $start, ?string $end): void
    {
        $practiceId = PracticeContext::currentPracticeId();
        $timezone = $practiceId
            ? Practice::find($practiceId)?->timezone ?? 'UTC'
            : 'UTC';

        $appointment = Appointment::where('id', $id)
            ->where('practice_id', $practiceId)
            ->firstOrFail();

        $data = [
            'start_datetime' => $this->normalizeCalendarDateTime($start, $timezone),
        ];

        if ($end) {
            $data['end_datetime'] = $this->normalizeCalendarDateTime($end, $timezone);
        }

        $appointment->update($data);

        Notification::make()->title('Appointment updated')->success()->send();
    }

    private function normalizeCalendarDateTime(string $value, string $timezone): string
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $value, $timezone)
            ->utc()
            ->format('Y-m-d H:i:s');
    }
}
