<?php

namespace App\Filament\Resources\AppointmentTypes\Pages;

use App\Filament\Resources\AppointmentTypes\AppointmentTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAppointmentType extends EditRecord
{
    protected static string $resource = AppointmentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
