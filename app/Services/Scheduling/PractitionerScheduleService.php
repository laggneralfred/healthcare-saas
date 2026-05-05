<?php

namespace App\Services\Scheduling;

use App\Models\Practitioner;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PractitionerScheduleService
{
    public function workingWindowsForDate(Practitioner $practitioner, Carbon $date): Collection
    {
        $timezone = $this->timezoneFor($practitioner);
        $localDate = $date->copy()->timezone($timezone);
        $dayOfWeek = $localDate->dayOfWeek;

        return $practitioner->workingHours()
            ->withoutGlobalScopes()
            ->where('practice_id', $practitioner->practice_id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get()
            ->map(fn ($workingHour): array => [
                'start' => $localDate->copy()->setTimeFromTimeString($workingHour->start_time),
                'end' => $localDate->copy()->setTimeFromTimeString($workingHour->end_time),
                'working_hour' => $workingHour,
            ]);
    }

    public function blocksForRange(Practitioner $practitioner, Carbon $from, Carbon $to): Collection
    {
        return $practitioner->timeBlocks()
            ->withoutGlobalScopes()
            ->where('practice_id', $practitioner->practice_id)
            ->where('starts_at', '<', $to)
            ->where('ends_at', '>', $from)
            ->orderBy('starts_at')
            ->get();
    }

    public function isWorkingAt(Practitioner $practitioner, Carbon $dateTime): bool
    {
        $timezone = $this->timezoneFor($practitioner);
        $localDateTime = $dateTime->copy()->timezone($timezone);

        $insideWorkingWindow = $this->workingWindowsForDate($practitioner, $localDateTime)
            ->contains(fn (array $window): bool => $localDateTime->gte($window['start']) && $localDateTime->lt($window['end']));

        if (! $insideWorkingWindow) {
            return false;
        }

        return $this->blocksForRange(
            $practitioner,
            $localDateTime->copy()->subSecond(),
            $localDateTime->copy()->addSecond(),
        )->isEmpty();
    }

    public function isWorkingForRange(Practitioner $practitioner, Carbon $start, Carbon $end): bool
    {
        if ($start->gte($end)) {
            return false;
        }

        $timezone = $this->timezoneFor($practitioner);
        $localStart = $start->copy()->timezone($timezone);
        $localEnd = $end->copy()->timezone($timezone);

        if (! $localStart->isSameDay($localEnd)) {
            return false;
        }

        $insideWorkingWindow = $this->workingWindowsForDate($practitioner, $localStart)
            ->contains(fn (array $window): bool => $localStart->gte($window['start']) && $localEnd->lte($window['end']));

        if (! $insideWorkingWindow) {
            return false;
        }

        return $this->blocksForRange($practitioner, $localStart, $localEnd)->isEmpty();
    }

    public function explainScheduleConflict(Practitioner $practitioner, Carbon $start, Carbon $end): ?string
    {
        if ($start->gte($end)) {
            return 'End time must be after start time.';
        }

        $timezone = $this->timezoneFor($practitioner);
        $localStart = $start->copy()->timezone($timezone);
        $localEnd = $end->copy()->timezone($timezone);

        if (! $localStart->isSameDay($localEnd)) {
            return 'Appointments must fit within one working day.';
        }

        $insideWorkingWindow = $this->workingWindowsForDate($practitioner, $localStart)
            ->contains(fn (array $window): bool => $localStart->gte($window['start']) && $localEnd->lte($window['end']));

        if (! $insideWorkingWindow) {
            return 'Selected time falls outside this practitioner\'s working hours.';
        }

        $block = $this->blocksForRange($practitioner, $localStart, $localEnd)->first();

        if ($block) {
            $label = $block->reason ?: str_replace('_', ' ', $block->block_type);

            return "Selected time overlaps a practitioner time block: {$label}.";
        }

        return null;
    }

    private function timezoneFor(Practitioner $practitioner): string
    {
        return $practitioner->practice?->timezone ?: config('app.timezone');
    }
}
