<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    public function mount(): void
    {
        parent::mount();

        if ($patientId = request('patient_id')) {
            $this->form->fill(['patient_id' => $patientId]);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['practice_id'] = auth()->user()->practice_id;
        unset($data['duration_minutes']);
        return $data;
    }
}
