<?php

namespace App\Filament\Resources\MedicalHistories\Pages;

use App\Filament\Resources\MedicalHistories\MedicalHistoryResource;
use App\Filament\Resources\Patients\PatientResource;
use App\Models\Practitioner;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditMedicalHistory extends EditRecord
{
    protected static string $resource = MedicalHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->validateAssignedPractitioner($data);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        $patientId = $this->record->patient_id;

        return $patientId
            ? PatientResource::getUrl('view', ['record' => $patientId])
            : $this->getResource()::getUrl('index');
    }

    private function validateAssignedPractitioner(array $data): void
    {
        if (blank($data['practitioner_id'] ?? null)) {
            return;
        }

        $valid = Practitioner::withoutPracticeScope()
            ->whereKey($data['practitioner_id'])
            ->where('practice_id', $this->record->practice_id)
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages([
                'data.practitioner_id' => 'Select a practitioner in the current practice.',
            ]);
        }
    }
}
