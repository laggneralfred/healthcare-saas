<?php

namespace App\Filament\Resources\Practices\Pages;

use App\Filament\Resources\Practices\PracticeResource;
use App\Support\PracticeType;
use Filament\Resources\Pages\CreateRecord;

class CreatePractice extends CreateRecord
{
    protected static string $resource = PracticeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['discipline'] = PracticeType::disciplineFallback($data['practice_type'] ?? PracticeType::GENERAL_WELLNESS);

        return $data;
    }
}
