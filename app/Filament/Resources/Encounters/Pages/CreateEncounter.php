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
            if ($practitioner && $practitioner->specialty) {
                $specialty = $practitioner->specialty;
                $disciplineMap = [
                    'Acupuncture' => 'acupuncture',
                    'Acupuncture & Oriental Medicine' => 'acupuncture',
                    'Traditional Chinese Medicine' => 'acupuncture',
                    'Massage Therapy' => 'massage',
                    'Massage' => 'massage',
                    'Chiropractic' => 'chiropractic',
                    'Chiropractic Care' => 'chiropractic',
                    'Physical Therapy' => 'physiotherapy',
                    'Physiotherapy' => 'physiotherapy',
                ];
                $data['discipline'] = $disciplineMap[$specialty] ?? null;
            }
        }

        return $data;
    }
}
