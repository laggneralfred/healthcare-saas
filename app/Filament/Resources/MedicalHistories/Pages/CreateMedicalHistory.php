<?php

namespace App\Filament\Resources\MedicalHistories\Pages;

use App\Filament\Resources\MedicalHistories\MedicalHistoryResource;
use App\Models\Practitioner;
use App\Services\PracticeContext;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

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
        $data['practice_id'] = PracticeContext::currentPracticeId();
        $this->validateAssignedPractitioner($data);

        return $data;
    }

    private function validateAssignedPractitioner(array $data): void
    {
        if (blank($data['practitioner_id'] ?? null)) {
            return;
        }

        $valid = Practitioner::withoutPracticeScope()
            ->whereKey($data['practitioner_id'])
            ->where('practice_id', $data['practice_id'])
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages([
                'data.practitioner_id' => 'Select a practitioner in the current practice.',
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        $patientId = $this->record->patient_id;

        return $patientId
            ? \App\Filament\Resources\Patients\PatientResource::getUrl('view', ['record' => $patientId])
            : $this->getResource()::getUrl('index');
    }
}
