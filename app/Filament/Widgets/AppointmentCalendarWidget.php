<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class AppointmentCalendarWidget extends Widget
{
    protected string $view = 'filament.widgets.appointment-calendar';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    protected function getViewData(): array
    {
        return [
            'eventsUrl'     => route('admin.calendar.events'),
            'createBaseUrl' => AppointmentResource::getUrl('create'),
        ];
    }

    public function updateAppointmentTime(int $id, string $start, ?string $end): void
    {
        $appointment = Appointment::where('id', $id)
            ->where('practice_id', auth()->user()->practice_id)
            ->firstOrFail();

        $data = ['start_datetime' => $start];
        if ($end) {
            $data['end_datetime'] = $end;
        }

        $appointment->update($data);

        Notification::make()->title('Appointment updated')->success()->send();
    }
}
