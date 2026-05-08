<?php

namespace App\Services\Billing;

use App\Models\Practice;
use Carbon\Carbon;

class StripeSubscriptionSyncService
{
    /**
     * Sync all Stripe subscriptions for a single practice into the local DB.
     *
     * @return array{created: int, updated: int, skipped: int, error?: string}
     */
    public function syncPractice(Practice $practice): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        try {
            $stripeCustomer = $practice->asStripeCustomer(['subscriptions']);
            $stripeSubs = $stripeCustomer->subscriptions->data ?? [];
        } catch (\Throwable $e) {
            return array_merge(compact('created', 'updated', 'skipped'), [
                'error' => $e->getMessage(),
            ]);
        }

        foreach ($stripeSubs as $stripeSub) {
            $priceId = $stripeSub->items->data[0]->price->id ?? null;
            $quantity = $stripeSub->items->data[0]->quantity ?? 1;

            $endsAt = match (true) {
                $stripeSub->cancel_at_period_end => Carbon::createFromTimestamp($stripeSub->current_period_end),
                (bool) $stripeSub->ended_at => Carbon::createFromTimestamp($stripeSub->ended_at),
                default => null,
            };

            $payload = [
                'type' => 'default',
                'stripe_status' => $stripeSub->status,
                'stripe_price' => $priceId,
                'quantity' => $quantity,
                'trial_ends_at' => $stripeSub->trial_end
                    ? Carbon::createFromTimestamp($stripeSub->trial_end)
                    : null,
                'ends_at' => $endsAt,
            ];

            $existing = $practice->subscriptions()
                ->where('stripe_id', $stripeSub->id)
                ->first();

            if ($existing) {
                if ($existing->stripe_status !== $stripeSub->status
                    || $existing->stripe_price !== $priceId) {
                    $existing->update($payload);
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                $practice->subscriptions()->create(
                    array_merge(['stripe_id' => $stripeSub->id], $payload)
                );
                $created++;
            }
        }

        return compact('created', 'updated', 'skipped');
    }
}
