<?php

namespace App\Filament\Resources\Patients\Pages;

use App\Filament\Concerns\ShowsHipaaBaaAcknowledgementWarning;
use App\Filament\Resources\Patients\PatientResource;
use App\Filament\Widgets\HipaaBaaAcknowledgementWarningWidget;
use Filament\Resources\Pages\CreateRecord;

class CreatePatient extends CreateRecord
{
    use ShowsHipaaBaaAcknowledgementWarning;

    protected static string $resource = PatientResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['practice_id'] = auth()->user()->practice_id;
        return $data;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            HipaaBaaAcknowledgementWarningWidget::class,
        ];
    }
}
