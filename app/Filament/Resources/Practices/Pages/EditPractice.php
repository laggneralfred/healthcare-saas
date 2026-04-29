<?php

namespace App\Filament\Resources\Practices\Pages;

use App\Filament\Resources\Practices\PracticeResource;
use App\Support\PracticeType;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPractice extends EditRecord
{
    protected static string $resource = PracticeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['discipline'] = PracticeType::disciplineFallback($data['practice_type'] ?? PracticeType::GENERAL_WELLNESS);

        return $data;
    }
}
