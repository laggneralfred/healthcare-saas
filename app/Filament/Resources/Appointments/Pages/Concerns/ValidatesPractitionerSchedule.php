<?php

namespace App\Filament\Resources\Appointments\Pages\Concerns;

use App\Models\Practitioner;
use App\Services\Scheduling\PractitionerScheduleService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

trait ValidatesPractitionerSchedule
{
    protected function validatePractitionerSchedule(array $data): void
    {
        $practiceId = auth()->user()?->practice_id;
        $practitionerId = $data['practitioner_id'] ?? null;
        $start = $data['start_datetime'] ?? null;
        $end = $data['end_datetime'] ?? null;

        if (! $practiceId || ! $practitionerId || ! $start || ! $end) {
            return;
        }

        $practitioner = Practitioner::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->find($practitionerId);

        if (! $practitioner) {
            throw ValidationException::withMessages([
                'practitioner_id' => 'Choose a practitioner in this practice.',
            ]);
        }

        $timezone = $practitioner->practice?->timezone ?: config('app.timezone');
        $start = $start instanceof Carbon ? $start : Carbon::parse($start, $timezone);
        $end = $end instanceof Carbon ? $end : Carbon::parse($end, $timezone);
        $message = app(PractitionerScheduleService::class)->explainScheduleConflict($practitioner, $start, $end);

        if ($message) {
            throw ValidationException::withMessages([
                'start_datetime' => $message,
            ]);
        }
    }
}
