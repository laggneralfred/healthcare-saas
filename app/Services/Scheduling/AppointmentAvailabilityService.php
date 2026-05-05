<?php

namespace App\Services\Scheduling;

use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\AppointmentType;
use App\Models\Practice;
use App\Models\Practitioner;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AppointmentAvailabilityService
{
    public function __construct(private readonly PractitionerScheduleService $scheduleService)
    {
    }

    public function availableSlotsForRequest(AppointmentRequest $request, Carbon $from, Carbon $to): Collection
    {
        $request = AppointmentRequest::withoutPracticeScope()
            ->with(['practice', 'appointmentType', 'practitioner.user'])
            ->where('practice_id', $request->practice_id)
            ->find($request->id);

        if (! $request?->practice || ! $request->appointmentType) {
            return collect();
        }

        return $this->availableSlots(
            $request->practice,
            $request->appointmentType,
            $request->practitioner,
            $from,
            $to,
        );
    }

    public function availableSlots(Practice $practice, AppointmentType $appointmentType, ?Practitioner $practitioner, Carbon $from, Carbon $to): Collection
    {
        if ((int) $appointmentType->practice_id !== (int) $practice->id || $from->gte($to)) {
            return collect();
        }

        $timezone = $practice->timezone ?: config('app.timezone');
        $duration = (int) ($appointmentType->duration_minutes ?: $practice->default_appointment_duration ?: 60);
        $interval = (int) config('scheduling.slot_interval_minutes', 15);
        $localFrom = $from->copy()->timezone($timezone);
        $localTo = $to->copy()->timezone($timezone);

        return $this->eligiblePractitioners($practice, $appointmentType, $practitioner)
            ->flatMap(fn (Practitioner $eligiblePractitioner): Collection => $this->slotsForPractitioner(
                $practice,
                $appointmentType,
                $eligiblePractitioner,
                $localFrom,
                $localTo,
                $duration,
                $interval,
            ))
            ->sortBy(fn (array $slot): string => $slot['starts_at']->format('Y-m-d H:i:s') . '-' . $slot['practitioner_name'])
            ->values();
    }

    private function eligiblePractitioners(Practice $practice, AppointmentType $appointmentType, ?Practitioner $practitioner): Collection
    {
        $query = Practitioner::withoutPracticeScope()
            ->with('user')
            ->where('practice_id', $practice->id)
            ->where('is_active', true)
            ->whereHas('appointmentTypes', function ($query) use ($appointmentType, $practice): void {
                $query->where('appointment_types.id', $appointmentType->id)
                    ->where('practitioner_appointment_type.practice_id', $practice->id)
                    ->where('practitioner_appointment_type.is_active', true);
            });

        if ($practitioner) {
            $query->whereKey($practitioner->id);
        }

        return $query->get();
    }

    private function slotsForPractitioner(
        Practice $practice,
        AppointmentType $appointmentType,
        Practitioner $practitioner,
        Carbon $from,
        Carbon $to,
        int $duration,
        int $interval,
    ): Collection {
        $slots = collect();
        $date = $from->copy()->startOfDay();

        while ($date->lte($to)) {
            foreach ($this->scheduleService->workingWindowsForDate($practitioner, $date) as $window) {
                $candidate = $window['start']->copy()->max($from);
                $windowEnd = $window['end']->copy()->min($to);

                while ($candidate->copy()->addMinutes($duration)->lte($windowEnd)) {
                    $slotStart = $candidate->copy();
                    $slotEnd = $candidate->copy()->addMinutes($duration);

                    if (
                        $this->scheduleService->isWorkingForRange($practitioner, $slotStart, $slotEnd)
                        && ! $this->hasBusyAppointment($practice, $practitioner, $slotStart, $slotEnd)
                    ) {
                        $slots->push($this->slotPayload($appointmentType, $practitioner, $slotStart, $slotEnd));
                    }

                    $candidate->addMinutes($interval);
                }
            }

            $date->addDay();
        }

        return $slots;
    }

    private function hasBusyAppointment(Practice $practice, Practitioner $practitioner, Carbon $start, Carbon $end): bool
    {
        return Appointment::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('practitioner_id', $practitioner->id)
            ->whereIn('status', ['scheduled', 'confirmed', 'in_progress', 'checkout', 'completed'])
            ->where('start_datetime', '<', $end->format('Y-m-d H:i:s'))
            ->where('end_datetime', '>', $start->format('Y-m-d H:i:s'))
            ->exists();
    }

    private function slotPayload(AppointmentType $appointmentType, Practitioner $practitioner, Carbon $start, Carbon $end): array
    {
        $practitionerName = $practitioner->user?->name ?? "Practitioner #{$practitioner->id}";

        return [
            'practitioner_id' => $practitioner->id,
            'practitioner_name' => $practitionerName,
            'starts_at' => $start->copy(),
            'ends_at' => $end->copy(),
            'appointment_type_id' => $appointmentType->id,
            'label' => $start->format('D, M j g:i A') . ' - ' . $end->format('g:i A') . ' with ' . $practitionerName,
        ];
    }
}
