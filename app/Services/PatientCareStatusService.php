<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\States\Appointment\Cancelled;
use App\Models\States\Appointment\Checkout;
use App\Models\States\Appointment\Closed;
use App\Models\States\Appointment\Completed;
use App\Models\States\Appointment\InProgress;
use App\Models\States\Appointment\NoShow;
use App\Models\States\Appointment\Scheduled;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PatientCareStatusService
{
    public const STATUS_NEW = 'new';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_NEEDS_FOLLOW_UP = 'needs_follow_up';
    public const STATUS_COOLING = 'cooling';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_AT_RISK = 'at_risk';

    public const RECENTLY_SEEN_DAYS = 30;
    public const COOLING_DAYS = 45;
    public const INACTIVE_DAYS = 90;
    public const RECENT_RISK_EVENT_DAYS = 14;

    private const DEFINITIONS = [
        self::STATUS_NEW => [
            'label' => 'New',
            'helper' => 'This patient is just getting started with the practice.',
            'color' => 'info',
        ],
        self::STATUS_ACTIVE => [
            'label' => 'Active',
            'helper' => 'This patient has care already scheduled or was seen recently.',
            'color' => 'success',
        ],
        self::STATUS_NEEDS_FOLLOW_UP => [
            'label' => 'Needs Follow-Up',
            'helper' => 'This patient may benefit from a gentle check-in.',
            'color' => 'warning',
        ],
        self::STATUS_COOLING => [
            'label' => 'Cooling',
            'helper' => 'It has been a little while since this patient was last seen.',
            'color' => 'gray',
        ],
        self::STATUS_INACTIVE => [
            'label' => 'Inactive',
            'helper' => 'This patient has not been seen in a while.',
            'color' => 'gray',
        ],
        self::STATUS_AT_RISK => [
            'label' => 'At Risk',
            'helper' => 'A recent missed or canceled visit may need a caring follow-up.',
            'color' => 'danger',
        ],
    ];

    public function forPatient(Patient $patient, ?CarbonInterface $now = null): array
    {
        $now = Carbon::parse($now ?? now());

        if ($this->hasRecentRiskEvent($patient, $now) && ! $this->hasFutureAppointment($patient, $now)) {
            return $this->status(self::STATUS_AT_RISK);
        }

        if ($this->hasFutureAppointment($patient, $now)) {
            return $this->status(self::STATUS_ACTIVE);
        }

        $lastCompletedVisitAt = $this->lastCompletedVisitAt($patient, $now);

        if (! $lastCompletedVisitAt) {
            return $this->status(self::STATUS_NEW);
        }

        $daysSinceLastVisit = $lastCompletedVisitAt->diffInDays($now);

        if ($daysSinceLastVisit > self::INACTIVE_DAYS) {
            return $this->status(self::STATUS_INACTIVE);
        }

        if ($daysSinceLastVisit > self::COOLING_DAYS) {
            return $this->status(self::STATUS_COOLING);
        }

        if ($daysSinceLastVisit > self::RECENTLY_SEEN_DAYS) {
            return $this->status(self::STATUS_NEEDS_FOLLOW_UP);
        }

        return $this->status(self::STATUS_ACTIVE);
    }

    public function options(): array
    {
        return collect(self::DEFINITIONS)
            ->mapWithKeys(fn (array $definition, string $key): array => [$key => $definition['label']])
            ->all();
    }

    public function all(): array
    {
        return collect(array_keys(self::DEFINITIONS))
            ->map(fn (string $key): array => $this->status($key))
            ->all();
    }

    private function status(string $key): array
    {
        return [
            'key' => $key,
            ...self::DEFINITIONS[$key],
        ];
    }

    private function hasFutureAppointment(Patient $patient, CarbonInterface $now): bool
    {
        if ($patient->relationLoaded('appointments')) {
            return $patient->appointments
                ->where('practice_id', $patient->practice_id)
                ->contains(fn ($appointment): bool => $appointment->start_datetime
                    && $appointment->start_datetime->greaterThanOrEqualTo($now)
                    && in_array($this->appointmentStatusName($appointment->status), [
                        Scheduled::$name,
                        InProgress::$name,
                        'confirmed',
                    ], true));
        }

        return $patient->appointments()
            ->where('practice_id', $patient->practice_id)
            ->where('start_datetime', '>=', $now)
            ->whereIn('status', [
                Scheduled::$name,
                InProgress::$name,
                'confirmed',
            ])
            ->exists();
    }

    private function hasRecentRiskEvent(Patient $patient, CarbonInterface $now): bool
    {
        $recentBoundary = $now->copy()->subDays(self::RECENT_RISK_EVENT_DAYS);

        if ($patient->relationLoaded('appointments')) {
            return $patient->appointments
                ->where('practice_id', $patient->practice_id)
                ->contains(fn ($appointment): bool => in_array($this->appointmentStatusName($appointment->status), [
                    Cancelled::$name,
                    NoShow::$name,
                ], true) && (
                    ($appointment->start_datetime && $appointment->start_datetime->greaterThanOrEqualTo($recentBoundary))
                    || ($appointment->updated_at && $appointment->updated_at->greaterThanOrEqualTo($recentBoundary))
                ));
        }

        return $patient->appointments()
            ->where('practice_id', $patient->practice_id)
            ->whereIn('status', [
                Cancelled::$name,
                NoShow::$name,
            ])
            ->where(function ($query) use ($recentBoundary): void {
                $query->where('start_datetime', '>=', $recentBoundary)
                    ->orWhere('updated_at', '>=', $recentBoundary);
            })
            ->exists();
    }

    private function lastCompletedVisitAt(Patient $patient, CarbonInterface $now): ?CarbonInterface
    {
        $lastCompletedEncounter = $this->completedEncounters($patient)
            ->sortByDesc(fn ($encounter) => $encounter->completed_on ?? $encounter->visit_date)
            ->first();

        $lastCompletedAppointment = $this->completedAppointments($patient, $now)
            ->sortByDesc('start_datetime')
            ->first();

        return collect([
            $lastCompletedEncounter?->completed_on,
            $lastCompletedEncounter?->visit_date,
            $lastCompletedAppointment?->start_datetime,
        ])
            ->filter()
            ->map(fn ($date): CarbonInterface => Carbon::parse($date))
            ->sortDesc()
            ->first();
    }

    private function completedEncounters(Patient $patient): Collection
    {
        if ($patient->relationLoaded('encounters')) {
            return $patient->encounters
                ->where('practice_id', $patient->practice_id)
                ->where('status', 'complete')
                ->values();
        }

        return $patient->encounters()
            ->where('practice_id', $patient->practice_id)
            ->where('status', 'complete')
            ->orderByDesc('completed_on')
            ->orderByDesc('visit_date')
            ->get();
    }

    private function completedAppointments(Patient $patient, CarbonInterface $now): Collection
    {
        $completedStatuses = [
            Completed::$name,
            Checkout::$name,
            Closed::$name,
        ];

        if ($patient->relationLoaded('appointments')) {
            return $patient->appointments
                ->where('practice_id', $patient->practice_id)
                ->filter(fn ($appointment): bool => $appointment->start_datetime
                    && $appointment->start_datetime->lessThanOrEqualTo($now)
                    && in_array($this->appointmentStatusName($appointment->status), $completedStatuses, true))
                ->values();
        }

        return $patient->appointments()
            ->where('practice_id', $patient->practice_id)
            ->whereIn('status', $completedStatuses)
            ->where('start_datetime', '<=', $now)
            ->orderByDesc('start_datetime')
            ->get();
    }

    private function appointmentStatusName(mixed $status): string
    {
        if (is_object($status) && property_exists($status, 'name')) {
            return $status::$name;
        }

        return (string) $status;
    }
}
