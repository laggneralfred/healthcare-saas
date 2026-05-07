<?php

namespace App\Filament\Resources\Patients\Pages;

use App\Filament\Concerns\ShowsHipaaBaaAcknowledgementWarning;
use App\Filament\Resources\Patients\PatientResource;
use App\Filament\Widgets\HipaaBaaAcknowledgementWarningWidget;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPatient extends EditRecord
{
    use ShowsHipaaBaaAcknowledgementWarning;

    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            HipaaBaaAcknowledgementWarningWidget::class,
        ];
    }
}
