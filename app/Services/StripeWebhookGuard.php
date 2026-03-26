<?php

namespace App\Services;

use App\Models\WebhookEvent;

class StripeWebhookGuard
{
    /**
     * Return true if we have already successfully processed this Stripe event.
     *
     * A record that exists but has a null processed_at (e.g. from a previous
     * failed attempt) is NOT considered processed — it will be retried.
     */
    public function alreadyProcessed(string $eventId): bool
    {
        return WebhookEvent::where('stripe_event_id', $eventId)
            ->whereNotNull('processed_at')
            ->exists();
    }

    /**
     * Stamp the event as successfully processed.
     *
     * Clears any prior failed_at / error values so the record reflects the
     * final successful outcome.
     */
    public function markProcessed(string $eventId): void
    {
        WebhookEvent::where('stripe_event_id', $eventId)->update([
            'processed_at' => now(),
            'failed_at'    => null,
            'error'        => null,
        ]);
    }

    /**
     * Record a processing failure.
     *
     * processed_at is left null so a subsequent delivery of the same event
     * will be retried rather than skipped.
     */
    public function markFailed(string $eventId, string $error): void
    {
        WebhookEvent::where('stripe_event_id', $eventId)->update([
            'failed_at' => now(),
            'error'     => $error,
        ]);
    }
}
