<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\CheckoutSession;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\States\CheckoutSession\Paid;
use App\Models\States\CheckoutSession\PaymentDue;
use App\Services\PracticeContext;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Number;

class DashboardPage extends Page
{
    protected static ?string                    $slug                     = 'dashboard';
    protected static ?string                    $title                    = 'Dashboard';
    protected static ?string                    $navigationLabel          = 'Dashboard';
    protected static string|BackedEnum|null     $navigationIcon           = Heroicon::OutlinedHome;
    protected static bool                       $shouldRegisterNavigation = true;
    protected static ?int                       $navigationSort           = -1;
    protected string $view = 'filament.pages.dashboard';

    public function getViewData(): array
    {
        $practiceId = PracticeContext::currentPracticeId();
        $practice   = $practiceId ? Practice::find($practiceId) : null;

        if (! $practice) {
            return ['practice' => null];
        }

        $now = now($practice->timezone ?? 'UTC');
        $startOfToday = $now->copy()->startOfDay();
        $endOfToday = $now->copy()->endOfDay();
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfWeek();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Today's appointments
        $appointmentsToday = Appointment::where('practice_id', $practice->id)
            ->whereBetween('start_datetime', [$startOfToday, $endOfToday])
            ->count();

        $appointmentsTodayCompleted = Appointment::where('practice_id', $practice->id)
            ->where('status', 'completed')
            ->whereBetween('start_datetime', [$startOfToday, $endOfToday])
            ->count();

        // Appointment metrics (monthly)
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

        // Revenue metrics (weekly)
        $checkoutSessionsThisWeek = CheckoutSession::where('practice_id', $practice->id)
            ->whereBetween('paid_on', [$startOfWeek, $endOfWeek])
            ->get();

        $revenueThisWeek = $checkoutSessionsThisWeek
            ->filter(fn (CheckoutSession $s) => $s->state instanceof Paid)
            ->sum('amount_total');

        // Revenue metrics (monthly)
        $checkoutSessionsThisMonth = CheckoutSession::where('practice_id', $practice->id)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->get();

        $totalRevenue = $checkoutSessionsThisMonth
            ->filter(fn (CheckoutSession $s) => $s->state instanceof Paid)
            ->sum('amount_total');

        $pendingRevenue = $checkoutSessionsThisMonth
            ->filter(fn (CheckoutSession $s) => $s->state instanceof PaymentDue)
            ->sum('amount_total');

        $checkoutSessionsCompleted = $checkoutSessionsThisMonth
            ->filter(fn (CheckoutSession $s) => $s->state instanceof Paid)
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
            'appointmentsToday' => $appointmentsToday,
            'appointmentsTodayCompleted' => $appointmentsTodayCompleted,
            'appointmentsThisMonth' => $appointmentsThisMonth,
            'appointmentsCompleted' => $appointmentsCompleted,
            'appointmentsPending' => $appointmentsPending,
            'totalPatients' => $totalPatients,
            'newPatientsThisMonth' => $newPatientsThisMonth,
            'revenueThisWeek' => $revenueThisWeek,
            'totalRevenue' => $totalRevenue,
            'pendingRevenue' => $pendingRevenue,
            'checkoutSessionsCompleted' => $checkoutSessionsCompleted,
            'appointmentsByStatus' => $appointmentsByStatus,
            'revenueByPractitioner' => $revenueByPractitioner,
            'formattedRevenueThisWeek' => Number::currency($revenueThisWeek, 'USD'),
            'formattedRevenue' => Number::currency($totalRevenue, 'USD'),
            'formattedPendingRevenue' => Number::currency($pendingRevenue, 'USD'),
        ];
    }
}
