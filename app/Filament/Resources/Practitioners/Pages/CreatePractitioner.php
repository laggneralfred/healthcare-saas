<?php

namespace App\Filament\Resources\Practitioners\Pages;

use App\Filament\Resources\Practitioners\PractitionerResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePractitioner extends CreateRecord
{
    protected static string $resource = PractitionerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['practice_id'] = auth()->user()->practice_id;
        return $data;
    }
}
