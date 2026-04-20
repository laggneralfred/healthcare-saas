<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Appointments\AppointmentResource;
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
}
