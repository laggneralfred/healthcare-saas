<?php

namespace App\Filament\Resources\MedicalHistories\Pages;

use App\Filament\Resources\MedicalHistories\MedicalHistoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMedicalHistory extends EditRecord
{
    protected static string $resource = MedicalHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
