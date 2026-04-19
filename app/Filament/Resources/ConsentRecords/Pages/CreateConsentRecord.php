<?php

namespace App\Filament\Resources\ConsentRecords\Pages;

use App\Filament\Resources\ConsentRecords\ConsentRecordResource;
use Filament\Resources\Pages\CreateRecord;

class CreateConsentRecord extends CreateRecord
{
    protected static string $resource = ConsentRecordResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['practice_id'] = auth()->user()->practice_id;
        return $data;
    }
}
