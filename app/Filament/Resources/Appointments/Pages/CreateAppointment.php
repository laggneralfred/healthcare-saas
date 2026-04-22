<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Pages\SchedulePage;
use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    public function mount(): void
    {
        parent::mount();

        $fill = [];

        if ($patientId = request('patient_id')) {
            $fill['patient_id'] = $patientId;
        }

        if ($startDatetime = request('start_datetime')) {
            $fill['start_datetime'] = \Carbon\Carbon::parse($startDatetime)->format('Y-m-d H:i:00');
        }

        if ($fill) {
            $this->form->fill($fill);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['practice_id'] = auth()->user()->practice_id;
        unset($data['duration_minutes']);
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return SchedulePage::getUrl();
    }
}
