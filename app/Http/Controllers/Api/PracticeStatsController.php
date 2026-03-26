<?php

namespace App\Http\Controllers\Api;

use App\Models\Appointment;
use App\Models\CheckoutSession;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\States\CheckoutSession\Paid;
use App\Models\States\CheckoutSession\PaymentDue;
use App\Services\PracticeContext;
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
        // Super-admins (no practice_id) can access any practice.
        // Regular users are restricted to their own practice.
        if (! PracticeContext::isSuperAdmin() && $practice->id !== auth()->user()->practice_id) {
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
            ->filter(fn (CheckoutSession $s) => $s->state instanceof Paid)
            ->sum('amount_total');

        $revenuePending = $checkoutSessionsThisMonth
            ->filter(fn (CheckoutSession $s) => $s->state instanceof PaymentDue)
            ->sum('amount_total');

        $checkoutSessionsCompleted = $checkoutSessionsThisMonth
            ->filter(fn (CheckoutSession $s) => $s->state instanceof Paid)
            ->count();

        $checkoutSessionsPending = $checkoutSessionsThisMonth
            ->filter(fn (CheckoutSession $s) => $s->state instanceof PaymentDue)
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
