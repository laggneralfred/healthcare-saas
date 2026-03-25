<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\CheckoutSession;
use App\Models\Patient;
use App\Models\Practice;
use Filament\Pages\Page;
use Illuminate\Support\Number;

class DashboardPage extends Page
{
    protected static ?string $title = 'Dashboard';
    protected string $view = 'filament.pages.dashboard';
    protected static ?int $navigationSort = -1;

    public function getViewData(): array
    {
        $practice = auth()->user()->practice;

        if (!$practice) {
            return [];
        }

        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Appointment metrics
        $appointmentsThisMonth = Appointment::where('practice_id', $practice->id)
            ->whereBetween('start_datetime', [$startOfMonth, $endOfMonth])
            ->count();

        $appointmentsCompleted = Appointment::where('practice_id', $practice->id)
            ->where('status', 'completed')
            ->whereBetween('start_datetime', [$startOfMonth, $endOfMonth])
            ->count();

        $appointmentsPending = Appointment::where('practice_id', $practice->id)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->whereBetween('start_datetime', [$startOfMonth, $endOfMonth])
            ->count();

        // Patient metrics
        $totalPatients = Patient::where('practice_id', $practice->id)->count();

        $newPatientsThisMonth = Patient::where('practice_id', $practice->id)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        // Revenue metrics
        $checkoutSessionsThisMonth = CheckoutSession::where('practice_id', $practice->id)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->get();

        $totalRevenue = $checkoutSessionsThisMonth
            ->where('state.name', 'paid')
            ->sum('amount_total');

        $pendingRevenue = $checkoutSessionsThisMonth
            ->where('state.name', 'payment_due')
            ->sum('amount_total');

        $checkoutSessionsCompleted = $checkoutSessionsThisMonth
            ->where('state.name', 'paid')
            ->count();

        // Appointment status breakdown
        $appointmentsByStatus = Appointment::where('practice_id', $practice->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Revenue by practitioner
        $revenueByPractitioner = CheckoutSession::where('practice_id', $practice->id)
            ->where('state', 'paid')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->with('practitioner.user')
            ->get()
            ->groupBy('practitioner_id')
            ->map(function ($sessions) {
                return [
                    'practitioner_name' => $sessions->first()?->practitioner?->user?->name ?? 'Unknown',
                    'appointments' => $sessions->count(),
                    'revenue' => $sessions->sum('amount_total'),
                ];
            })
            ->values()
            ->toArray();

        return [
            'practice' => $practice,
            'appointmentsThisMonth' => $appointmentsThisMonth,
            'appointmentsCompleted' => $appointmentsCompleted,
            'appointmentsPending' => $appointmentsPending,
            'totalPatients' => $totalPatients,
            'newPatientsThisMonth' => $newPatientsThisMonth,
            'totalRevenue' => $totalRevenue,
            'pendingRevenue' => $pendingRevenue,
            'checkoutSessionsCompleted' => $checkoutSessionsCompleted,
            'appointmentsByStatus' => $appointmentsByStatus,
            'revenueByPractitioner' => $revenueByPractitioner,
            'formattedRevenue' => Number::currency($totalRevenue / 100, 'USD'),
            'formattedPendingRevenue' => Number::currency($pendingRevenue / 100, 'USD'),
        ];
    }
}
