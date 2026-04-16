<?php

namespace App\Filament\Resources\MedicalHistories\Pages;

use App\Filament\Resources\MedicalHistories\MedicalHistoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMedicalHistory extends CreateRecord
{
    protected static string $resource = MedicalHistoryResource::class;
}
