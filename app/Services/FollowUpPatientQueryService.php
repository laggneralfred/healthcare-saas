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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class FollowUpPatientQueryService
{
    public function candidatesForPractice(int $practiceId, ?CarbonInterface $now = null): Collection
    {
        $now = Carbon::parse($now ?? now());
        $recentlySeenBoundary = $now->copy()->subDays(PatientCareStatusService::RECENTLY_SEEN_DAYS);
        $recentRiskBoundary = $now->copy()->subDays(PatientCareStatusService::RECENT_RISK_EVENT_DAYS);

        return Patient::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->whereDoesntHave('appointments', function ($query) use ($practiceId, $now): void {
                $query->where('practice_id', $practiceId)
                    ->where('start_datetime', '>=', $now)
                    ->whereIn('status', [
                        Scheduled::$name,
                        InProgress::$name,
                        'confirmed',
                    ]);
            })
            ->where(function ($query) use ($practiceId, $recentlySeenBoundary, $recentRiskBoundary): void {
                $query
                    ->whereHas('appointments', function ($query) use ($practiceId, $recentRiskBoundary): void {
                        $query->where('practice_id', $practiceId)
                            ->whereIn('status', [
                                Cancelled::$name,
                                NoShow::$name,
                            ])
                            ->where(function ($query) use ($recentRiskBoundary): void {
                                $query->where('start_datetime', '>=', $recentRiskBoundary)
                                    ->orWhere('updated_at', '>=', $recentRiskBoundary);
                            });
                    })
                    ->orWhereHas('encounters', function ($query) use ($practiceId, $recentlySeenBoundary): void {
                        $query->where('practice_id', $practiceId)
                            ->where('status', 'complete')
                            ->where(function ($query) use ($recentlySeenBoundary): void {
                                $query->where('completed_on', '<=', $recentlySeenBoundary)
                                    ->orWhere(function ($query) use ($recentlySeenBoundary): void {
                                        $query->whereNull('completed_on')
                                            ->where('visit_date', '<=', $recentlySeenBoundary->toDateString());
                                    });
                            });
                    })
                    ->orWhereHas('appointments', function ($query) use ($practiceId, $recentlySeenBoundary): void {
                        $query->where('practice_id', $practiceId)
                            ->whereIn('status', [
                                Completed::$name,
                                Checkout::$name,
                                Closed::$name,
                            ])
                            ->where('start_datetime', '<=', $recentlySeenBoundary);
                    });
            })
            ->with([
                'appointments' => fn ($query) => $query->orderByDesc('start_datetime'),
                'encounters' => fn ($query) => $query->orderByDesc('visit_date'),
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }
}
