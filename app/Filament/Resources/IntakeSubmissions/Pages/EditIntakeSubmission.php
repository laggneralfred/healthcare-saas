<?php

namespace App\Filament\Resources\IntakeSubmissions\Pages;

use App\Filament\Resources\IntakeSubmissions\IntakeSubmissionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIntakeSubmission extends EditRecord
{
    protected static string $resource = IntakeSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
