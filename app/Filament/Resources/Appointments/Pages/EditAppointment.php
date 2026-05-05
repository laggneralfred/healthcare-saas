<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Pages\SchedulePage;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Appointments\Pages\Concerns\ValidatesPractitionerSchedule;
use App\Services\AuditLogger;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAppointment extends EditRecord
{
    use ValidatesPractitionerSchedule;

    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        AuditLogger::viewed($this->getRecord());
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->validatePractitionerSchedule($data);

        unset($data['duration_minutes']);
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return request('return_url') ?: SchedulePage::getUrl();
    }
}
