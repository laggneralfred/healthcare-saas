<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\CheckoutSession;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\States\CheckoutSession\Paid;
use App\Models\States\CheckoutSession\PaymentDue;
use App\Services\PracticeContext;
use App\Services\PracticeSetupChecklistService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Number;

class DashboardPage extends Page
{
    protected static ?string                    $slug                     = 'dashboard';
    protected static ?string                    $title                    = 'Reports';
    protected static ?string                    $navigationLabel          = 'Reports';
    protected static string|BackedEnum|null     $navigationIcon           = Heroicon::OutlinedHome;
    protected static string|\UnitEnum|null      $navigationGroup          = 'Reports';
    protected static bool                       $shouldRegisterNavigation = true;
    protected static ?int                       $navigationSort           = -1;
    protected string $view = 'filament.pages.dashboard';

    public function mount(): void
    {
        // Onboarding is now optional — no forced redirect
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\TodaysScheduleWidget::class,
        ];
    }

    public function getViewData(): array
    {
        $practiceId = PracticeContext::currentPracticeId();
        $practice   = $practiceId ? Practice::find($practiceId) : null;

        if (! $practice) {
            return ['practice' => null, 'showSetupBanner' => false];
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
        $revenueThisWeek = CheckoutSession::where('practice_id', $practice->id)
            ->where('state', Paid::$name)
            ->whereBetween('paid_on', [$startOfWeek, $endOfWeek])
            ->sum('amount_total');

        // Revenue metrics (monthly)
        $totalRevenue = CheckoutSession::where('practice_id', $practice->id)
            ->where('state', Paid::$name)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('amount_total');

        $pendingRevenue = CheckoutSession::where('practice_id', $practice->id)
            ->where('state', PaymentDue::$name)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('amount_total');

        $checkoutSessionsCompleted = CheckoutSession::where('practice_id', $practice->id)
            ->where('state', Paid::$name)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        // Appointment status breakdown
        $appointmentsByStatus = Appointment::where('practice_id', $practice->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Revenue by practitioner
        $revenueByPractitioner = CheckoutSession::where('practice_id', $practice->id)
            ->where('state', Paid::$name)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->with('practitioner.user')
            ->selectRaw('practitioner_id, COUNT(*) as appointments, SUM(amount_total) as revenue')
            ->groupBy('practitioner_id')
            ->get()
            ->map(function ($session) {
                return [
                    'practitioner_name' => $session->practitioner?->user?->name ?? 'Unknown',
                    'appointments' => $session->appointments,
                    'revenue' => $session->revenue,
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
            'setupChecklist' => app(PracticeSetupChecklistService::class)->forPractice($practice),
            'formattedRevenueThisWeek' => Number::currency($revenueThisWeek, 'USD'),
            'formattedRevenue' => Number::currency($totalRevenue, 'USD'),
            'formattedPendingRevenue' => Number::currency($pendingRevenue, 'USD'),
            'showSetupBanner' => ! $practice->is_demo
                && ! $practice->setup_completed_at
                && ! $practice->dismissed_onboarding_banner,
        ];
    }
}
