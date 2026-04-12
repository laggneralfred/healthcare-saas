<?php

namespace App\Filament\Resources\Encounters\Pages;

use App\Filament\Resources\Encounters\EncounterResource;
use App\Filament\Resources\Encounters\Schemas\EncounterForm;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;

class CreateEncounter extends CreateRecord
{
    protected static string $resource = EncounterResource::class;

    public function form(Schema $schema): Schema
    {
        return EncounterResource::form($schema);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-populate discipline from practitioner if not set
        if (!empty($data['practitioner_id'])) {
            $practitioner = \App\Models\Practitioner::find($data['practitioner_id']);
            if ($practitioner && $practitioner->user) {
                $specialty = $practitioner->user->specialty;
                if ($specialty) {
                    $disciplineMap = [
                        'Acupuncture' => 'acupuncture',
                        'Massage Therapy' => 'massage',
                        'Chiropractic' => 'chiropractic',
                        'Physical Therapy' => 'physiotherapy',
                    ];
                    $data['discipline'] = $disciplineMap[$specialty] ?? null;
                }
            }
        }

        return $data;
    }
}
