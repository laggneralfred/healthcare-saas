<?php

namespace App\Filament\Resources\Encounters\Pages;

use App\Filament\Resources\Encounters\EncounterResource;
use App\Filament\Resources\Encounters\Pages\Concerns\HandlesEncounterAIActions;
use App\Models\Appointment;
use App\Models\Practitioner;
use App\Services\EncounterDataValidator;
use App\Services\EncounterNoteDocument;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class CreateEncounter extends CreateRecord
{
    use HandlesEncounterAIActions;

    protected static string $resource = EncounterResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'New Visit';
    }

    public function form(Schema $schema): Schema
    {
        return EncounterResource::form($schema);
    }

    public function mount(): void
    {
        parent::mount();

        $data = [];

        if ($appointmentId = request('appointment_id')) {
            $appointment = Appointment::query()
                ->whereKey($appointmentId)
                ->with('practitioner')
                ->first();

            if ($appointment) {
                $data = [
                    'appointment_id' => $appointment->id,
                    'patient_id' => $appointment->patient_id,
                    'practitioner_id' => $appointment->practitioner_id,
                    'discipline' => $this->disciplineFromSpecialty($appointment->practitioner?->specialty),
                    'visit_date' => $appointment->start_datetime?->toDateString(),
                ];
            }
        }

        if (! $data && ($patientId = request('patient_id'))) {
            $data['patient_id'] = $patientId;
        }

        if ($data) {
            if (! empty($data['discipline'])) {
                $data['visit_note_document'] = EncounterNoteDocument::template($data['discipline']);
            }

            $this->form->fill(array_filter($data, fn ($value) => $value !== null));
        }
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Save Note');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = EncounterNoteDocument::applyToEncounterData($data, ! $this->insuranceBillingEnabledForAI());
        $data = EncounterDataValidator::forCurrentPractice($data);

        // Auto-populate discipline from practitioner if not set
        if (empty($data['discipline']) && ! empty($data['practitioner_id'])) {
            $practitioner = Practitioner::find($data['practitioner_id']);
            if ($practitioner && $practitioner->specialty) {
                $data['discipline'] = $this->disciplineFromSpecialty($practitioner->specialty);
            }
        }

        $data['status'] = 'draft';
        $data['completed_on'] = null;

        return $data;
    }

    private function disciplineFromSpecialty(?string $specialty): ?string
    {
        return match ($specialty) {
            'Acupuncture', 'Acupuncture & Oriental Medicine', 'Traditional Chinese Medicine' => 'acupuncture',
            'Massage Therapy', 'Massage' => 'massage',
            'Chiropractic', 'Chiropractic Care' => 'chiropractic',
            'Physical Therapy', 'Physiotherapy' => 'physiotherapy',
            default => null,
        };
    }
}
