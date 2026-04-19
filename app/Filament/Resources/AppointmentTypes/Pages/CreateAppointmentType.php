<?php

namespace App\Filament\Resources\AppointmentTypes\Pages;

use App\Filament\Resources\AppointmentTypes\AppointmentTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAppointmentType extends CreateRecord
{
    protected static string $resource = AppointmentTypeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['practice_id'] = auth()->user()->practice_id;
        return $data;
    }
}
