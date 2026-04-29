<?php

namespace App\Filament\Resources\PracticePaymentMethods\Pages;

use App\Filament\Resources\PracticePaymentMethods\PracticePaymentMethodResource;
use Filament\Resources\Pages\EditRecord;

class EditPracticePaymentMethod extends EditRecord
{
    protected static string $resource = PracticePaymentMethodResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['practice_id'], $data['method_key']);

        return $data;
    }
}
