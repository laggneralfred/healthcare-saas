<?php

namespace App\Filament\Resources\MedicalHistories\Pages;

use App\Filament\Resources\MedicalHistories\MedicalHistoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMedicalHistory extends CreateRecord
{
    protected static string $resource = MedicalHistoryResource::class;

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
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        $patientId = $this->record->patient_id;

        return $patientId
            ? \App\Filament\Resources\Patients\PatientResource::getUrl('view', ['record' => $patientId])
            : $this->getResource()::getUrl('index');
    }
}
