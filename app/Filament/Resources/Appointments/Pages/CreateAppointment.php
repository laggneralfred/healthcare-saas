<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Pages\SchedulePage;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Appointments\Pages\Concerns\ValidatesPractitionerSchedule;
use App\Models\AppointmentRequest;
use Filament\Resources\Pages\CreateRecord;

class CreateAppointment extends CreateRecord
{
    use ValidatesPractitionerSchedule;

    protected static string $resource = AppointmentResource::class;

    public ?AppointmentRequest $appointmentRequest = null;

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
            $duration               = auth()->user()->practice?->default_appointment_duration ?? 60;
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
}
