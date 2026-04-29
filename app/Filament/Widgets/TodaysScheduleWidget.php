<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Encounters\EncounterResource;
use App\Models\Appointment;
use App\Services\PracticeContext;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class TodaysScheduleWidget extends Widget
{
    protected string $view = 'filament.widgets.todays-schedule';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -10;

    protected function getViewData(): array
    {
        $practiceId = PracticeContext::currentPracticeId();

        if (! $practiceId) {
            return ['appointments' => new Collection];
        }

        $appointments = Appointment::where('practice_id', $practiceId)
            ->whereDate('start_datetime', today())
            ->with(['patient', 'practitioner.user', 'appointmentType', 'encounter'])
            ->orderBy('start_datetime')
            ->get();

        return [
            'appointments' => $appointments,
            'appointmentUrlFn' => fn ($id) => AppointmentResource::getUrl('view', ['record' => $id]),
            'newVisitUrlFn' => function (Appointment $a) {
                return EncounterResource::getUrl('create', [
                    'patient_id' => $a->patient_id,
                    'appointment_id' => $a->id,
                ]);
            },
        ];
    }
}
