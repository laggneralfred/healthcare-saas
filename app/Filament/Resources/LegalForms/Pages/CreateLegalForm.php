<?php

namespace App\Filament\Resources\LegalForms\Pages;

use App\Filament\Resources\LegalForms\LegalFormResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLegalForm extends CreateRecord
{
    protected static string $resource = LegalFormResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['practice_id'] = auth()->user()->practice_id;
        return $data;
    }
}
