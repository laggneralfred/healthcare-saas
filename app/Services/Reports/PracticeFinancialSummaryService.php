<?php

namespace App\Services\Reports;

use App\Models\CheckoutLine;
use App\Models\CheckoutPayment;
use App\Models\CheckoutSession;
use App\Models\Practice;
use App\Models\States\CheckoutSession\Open;
use App\Models\States\CheckoutSession\Paid;
use App\Models\States\CheckoutSession\PaymentDue;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PracticeFinancialSummaryService
{
    public function summarize(Practice $practice, Carbon $start, Carbon $end, ?string $timezone = null): array
    {
        $timezone ??= $practice->timezone ?: config('app.timezone', 'UTC');

        [$startAt, $endAt] = $this->dateBounds($start, $end, $timezone);

        $payments = CheckoutPayment::withoutPracticeScope()
            ->where('checkout_payments.practice_id', $practice->id)
            ->whereBetween('paid_at', [$startAt, $endAt])
            ->with([
                'checkoutSession.practitioner.user',
                'checkoutSession.patient',
                'createdBy',
            ])
            ->get();

        $sessionCollections = $this->sessionCollections($practice, $payments, $startAt, $endAt);

        return [
            'period' => [
                'start' => $startAt->copy()->timezone($timezone)->toDateString(),
                'end' => $endAt->copy()->timezone($timezone)->toDateString(),
                'timezone' => $timezone,
            ],
            'total_collected' => round((float) $payments->sum(fn (CheckoutPayment $payment) => (float) $payment->amount), 2),
            'payment_method_totals' => $this->paymentMethodTotals($payments),
            'practitioner_totals' => $this->practitionerTotals($payments),
            'line_type_totals' => $this->lineTypeTotals($sessionCollections['sessions'], $sessionCollections['collectedBySession']),
            'paid_sessions_count' => $sessionCollections['paidSessionsCount'],
            'collected_sessions_count' => $sessionCollections['collectedSessionsCount'],
            'unpaid_open_sessions_count' => $sessionCollections['unpaidOpenSessionsCount'],
            'unpaid_open_sessions_total' => $sessionCollections['unpaidOpenSessionsTotal'],
        ];
    }

    public function paymentsForExport(Practice $practice, Carbon $start, Carbon $end, ?string $timezone = null): Collection
    {
        $timezone ??= $practice->timezone ?: config('app.timezone', 'UTC');
        [$startAt, $endAt] = $this->dateBounds($start, $end, $timezone);

        return CheckoutPayment::withoutPracticeScope()
            ->where('checkout_payments.practice_id', $practice->id)
            ->whereBetween('paid_at', [$startAt, $endAt])
            ->with([
                'checkoutSession.patient',
                'checkoutSession.practitioner.user',
                'createdBy',
            ])
            ->orderBy('paid_at')
            ->get();
    }

    public function lineItemsForExport(Practice $practice, Carbon $start, Carbon $end, ?string $timezone = null): Collection
    {
        $timezone ??= $practice->timezone ?: config('app.timezone', 'UTC');
        [$startAt, $endAt] = $this->dateBounds($start, $end, $timezone);

        $sessionIds = CheckoutPayment::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->whereBetween('paid_at', [$startAt, $endAt])
            ->pluck('checkout_session_id')
            ->unique()
            ->values();

        if ($sessionIds->isEmpty()) {
            return collect();
        }

        return CheckoutLine::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->whereIn('checkout_session_id', $sessionIds)
            ->with([
                'checkoutSession.checkoutPayments',
                'checkoutSession.practitioner.user',
                'checkoutSession.appointment.appointmentType',
                'serviceFee',
                'inventoryProduct',
            ])
            ->orderBy('checkout_session_id')
            ->orderBy('sequence')
            ->get();
    }

    private function dateBounds(Carbon $start, Carbon $end, string $timezone): array
    {
        return [
            $start->copy()->timezone($timezone)->startOfDay()->utc(),
            $end->copy()->timezone($timezone)->endOfDay()->utc(),
        ];
    }

    private function paymentMethodTotals(Collection $payments): Collection
    {
        return $payments
            ->groupBy('payment_method')
            ->map(fn (Collection $group, string $method) => [
                'payment_method' => $method,
                'label' => CheckoutPayment::METHODS[$method] ?? ucfirst(str_replace('_', ' ', $method)),
                'count' => $group->count(),
                'total' => round((float) $group->sum(fn (CheckoutPayment $payment) => (float) $payment->amount), 2),
            ])
            ->sortBy('label')
            ->values();
    }

    private function practitionerTotals(Collection $payments): Collection
    {
        return $payments
            ->groupBy(fn (CheckoutPayment $payment) => (string) ($payment->checkoutSession?->practitioner_id ?: 'unassigned'))
            ->map(function (Collection $group, string $key) {
                $session = $group->first()?->checkoutSession;
                $practitioner = $session?->practitioner;

                return [
                    'practitioner_id' => $key === 'unassigned' ? null : (int) $key,
                    'practitioner_name' => $practitioner?->user?->name ?? 'Unassigned',
                    'payment_count' => $group->count(),
                    'total' => round((float) $group->sum(fn (CheckoutPayment $payment) => (float) $payment->amount), 2),
                ];
            })
            ->sortBy('practitioner_name')
            ->values();
    }

    private function sessionCollections(Practice $practice, Collection $payments, Carbon $startAt, Carbon $endAt): array
    {
        $collectedBySession = $payments
            ->groupBy('checkout_session_id')
            ->map(fn (Collection $group) => round((float) $group->sum(fn (CheckoutPayment $payment) => (float) $payment->amount), 2));

        $sessions = $collectedBySession->isEmpty()
            ? collect()
            : CheckoutSession::withoutPracticeScope()
                ->where('practice_id', $practice->id)
                ->whereIn('id', $collectedBySession->keys())
                ->with('checkoutLines')
                ->get();

        $unpaidOpenSessions = CheckoutSession::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->whereIn('state', [Open::$name, PaymentDue::$name])
            ->whereBetween('created_at', [$startAt, $endAt])
            ->get();

        return [
            'sessions' => $sessions,
            'collectedBySession' => $collectedBySession,
            'paidSessionsCount' => $sessions
                ->filter(fn (CheckoutSession $session) => $session->state instanceof Paid)
                ->count(),
            'collectedSessionsCount' => $sessions->count(),
            'unpaidOpenSessionsCount' => $unpaidOpenSessions->count(),
            'unpaidOpenSessionsTotal' => round((float) $unpaidOpenSessions->sum(fn (CheckoutSession $session) => (float) $session->amount_due), 2),
        ];
    }

    private function lineTypeTotals(Collection $sessions, Collection $collectedBySession): Collection
    {
        $totals = collect([
            CheckoutLine::TYPE_SERVICE => ['line_type' => CheckoutLine::TYPE_SERVICE, 'label' => CheckoutLine::TYPES[CheckoutLine::TYPE_SERVICE], 'line_count' => 0, 'total' => 0.0],
            CheckoutLine::TYPE_INVENTORY => ['line_type' => CheckoutLine::TYPE_INVENTORY, 'label' => CheckoutLine::TYPES[CheckoutLine::TYPE_INVENTORY], 'line_count' => 0, 'total' => 0.0],
            CheckoutLine::TYPE_CUSTOM => ['line_type' => CheckoutLine::TYPE_CUSTOM, 'label' => CheckoutLine::TYPES[CheckoutLine::TYPE_CUSTOM], 'line_count' => 0, 'total' => 0.0],
        ]);

        foreach ($sessions as $session) {
            $amountTotal = (float) $session->amount_total;
            $collected = (float) ($collectedBySession[$session->id] ?? 0);
            $ratio = $amountTotal > 0 ? min($collected, $amountTotal) / $amountTotal : 0;

            foreach ($session->checkoutLines as $line) {
                $type = $line->line_type ?: CheckoutLine::TYPE_CUSTOM;
                $row = $totals->get($type, [
                    'line_type' => $type,
                    'label' => ucfirst(str_replace('_', ' ', $type)),
                    'line_count' => 0,
                    'total' => 0.0,
                ]);

                $row['line_count']++;
                $row['total'] = round($row['total'] + ((float) $line->amount * $ratio), 2);
                $totals->put($type, $row);
            }
        }

        return $totals
            ->values()
            ->filter(fn (array $row) => $row['line_count'] > 0 || $row['total'] > 0)
            ->values();
    }
}
