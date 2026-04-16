<?php

namespace App\Filament\Resources\MedicalHistories\Pages;

use App\Filament\Resources\MedicalHistories\MedicalHistoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMedicalHistories extends ListRecords
{
    protected static string $resource = MedicalHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
