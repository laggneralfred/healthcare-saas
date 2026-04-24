<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use App\Models\Practice;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use App\Services\PracticeContext;
use Illuminate\Support\Carbon;

class AppointmentCalendarWidget extends Widget
{
    protected string $view = 'filament.widgets.appointment-calendar';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    protected function getViewData(): array
    {
        $practiceId = PracticeContext::currentPracticeId();
        $timezone = $practiceId
            ? Practice::find($practiceId)?->timezone ?? 'UTC'
            : 'UTC';

        return [
            'eventsUrl'     => route('admin.calendar.events'),
            'createBaseUrl' => AppointmentResource::getUrl('create'),
            'calendarTimezone' => $timezone,
        ];
    }

    public function updateAppointmentTime(int $id, string $start, ?string $end): void
    {
        $practiceId = PracticeContext::currentPracticeId();
        $timezone = $practiceId
            ? Practice::find($practiceId)?->timezone ?? 'UTC'
            : 'UTC';

        $appointment = Appointment::where('id', $id)
            ->where('practice_id', $practiceId)
            ->firstOrFail();

        $data = [
            'start_datetime' => $this->normalizeCalendarDateTime($start, $timezone),
        ];

        if ($end) {
            $data['end_datetime'] = $this->normalizeCalendarDateTime($end, $timezone);
        }

        $appointment->update($data);

        Notification::make()->title('Appointment updated')->success()->send();
    }

    private function normalizeCalendarDateTime(string $value, string $timezone): string
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $value, $timezone)
            ->utc()
            ->format('Y-m-d H:i:s');
    }
}
