<?php

namespace App\Http\Controllers\Api;

use App\Models\Appointment;
use App\Models\CheckoutSession;
use App\Models\Patient;
use App\Models\Practice;
use Illuminate\Http\JsonResponse;

class PracticeStatsController
{
    /**
     * Get comprehensive statistics for a practice
     *
     * @authenticated
     * @param Practice $practice
     * @return JsonResponse
     */
    public function show(Practice $practice): JsonResponse
    {
        // Verify authorization
        if ($practice->id !== auth()->user()->practice_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Appointment metrics
        $appointmentsTotal = Appointment::where('practice_id', $practice->id)->count();
        $appointmentsThisMonth = Appointment::where('practice_id', $practice->id)
            ->whereBetween('start_datetime', [$startOfMonth, $endOfMonth])
            ->count();

        $appointmentsByStatus = Appointment::where('practice_id', $practice->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Patient metrics
        $patientsTotal = Patient::where('practice_id', $practice->id)->count();
        $patientsNewThisMonth = Patient::where('practice_id', $practice->id)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        // Revenue metrics
        $checkoutSessionsThisMonth = CheckoutSession::where('practice_id', $practice->id)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->get();

        $revenuePaid = $checkoutSessionsThisMonth
            ->where('state.name', 'paid')
            ->sum('amount_total');

        $revenuePending = $checkoutSessionsThisMonth
            ->where('state.name', 'payment_due')
            ->sum('amount_total');

        $checkoutSessionsCompleted = $checkoutSessionsThisMonth
            ->where('state.name', 'paid')
            ->count();

        $checkoutSessionsPending = $checkoutSessionsThisMonth
            ->where('state.name', 'payment_due')
            ->count();

        return response()->json([
            'practice' => [
                'id'   => $practice->id,
                'name' => $practice->name,
                'slug' => $practice->slug,
            ],
            'period' => [
                'month'  => now()->format('F Y'),
                'start'  => $startOfMonth->toDateString(),
                'end'    => $endOfMonth->toDateString(),
            ],
            'appointments' => [
                'total'       => $appointmentsTotal,
                'this_month'  => $appointmentsThisMonth,
                'by_status'   => $appointmentsByStatus,
            ],
            'patients' => [
                'total'              => $patientsTotal,
                'new_this_month'     => $patientsNewThisMonth,
            ],
            'revenue' => [
                'paid_cents'         => $revenuePaid,
                'paid_dollars'       => round($revenuePaid / 100, 2),
                'pending_cents'      => $revenuePending,
                'pending_dollars'    => round($revenuePending / 100, 2),
                'total_cents'        => $revenuePaid + $revenuePending,
                'total_dollars'      => round(($revenuePaid + $revenuePending) / 100, 2),
            ],
            'checkout_sessions' => [
                'completed_this_month' => $checkoutSessionsCompleted,
                'pending_this_month'   => $checkoutSessionsPending,
            ],
            'subscription' => [
                'plan'      => $practice->currentPlan()?->key,
                'plan_name' => $practice->currentPlan()?->name,
                'active'    => $practice->subscribed('default'),
            ],
        ]);
    }
}
