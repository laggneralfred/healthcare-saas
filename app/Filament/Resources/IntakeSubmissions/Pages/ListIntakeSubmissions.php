<?php

namespace App\Filament\Resources\IntakeSubmissions\Pages;

use App\Filament\Resources\IntakeSubmissions\IntakeSubmissionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIntakeSubmissions extends ListRecords
{
    protected static string $resource = IntakeSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
