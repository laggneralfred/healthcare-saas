<?php

namespace App\Http\Controllers;

use App\Models\WebhookEvent;
use App\Services\StripeWebhookGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends CashierWebhookController
{
    public function __construct(private readonly StripeWebhookGuard $guard)
    {
    }

    // ── Idempotency wrapper ────────────────────────────────────────────────────

    /**
     * Handle an incoming Stripe webhook with idempotency protection.
     *
     * Flow:
     *   1. Parse the raw payload to extract the Stripe event ID.
     *   2. If we already processed this event successfully → return 200 immediately.
     *   3. Create a WebhookEvent audit row (or find the existing one from a
     *      previous failed attempt).
     *   4. Call the parent handler, which verifies the Stripe signature and
     *      routes to the appropriate handleXxx() method on this class.
     *   5. On a 2xx response → markProcessed().
     *   6. On any exception → markFailed() then re-throw.
     */
    public function handleWebhook(Request $request): Response
    {
        $rawPayload = json_decode($request->getContent(), true);
        $eventId    = $rawPayload['id']   ?? null;
        $eventType  = $rawPayload['type'] ?? 'unknown';

        // ── Step 2: Early return for already-processed events ─────────────────
        if ($eventId && $this->guard->alreadyProcessed($eventId)) {
            Log::info('Stripe webhook already processed — skipping', [
                'event_id' => $eventId,
                'type'     => $eventType,
            ]);

            return new Response('', 200);
        }

        // ── Step 3: Ensure an audit row exists before processing ──────────────
        if ($eventId) {
            WebhookEvent::firstOrCreate(
                ['stripe_event_id' => $eventId],
                ['type' => $eventType, 'payload' => $rawPayload],
            );
        }

        // ── Steps 4–6: Process with guard ─────────────────────────────────────
        try {
            // parent::handleWebhook() verifies the Stripe signature and dispatches
            // to handleCustomerSubscriptionCreated(), handleInvoicePaymentFailed(),
            // etc., including any Cashier built-in handlers we have not overridden.
            $response = parent::handleWebhook($request);

            if ($eventId && $response->getStatusCode() < 300) {
                $this->guard->markProcessed($eventId);
            }

            return $response;
        } catch (\Throwable $e) {
            if ($eventId) {
                $this->guard->markFailed($eventId, $e->getMessage());
            }
            throw $e;
        }
    }

    // ── Individual handlers ────────────────────────────────────────────────────
    // These are called by parent::handleWebhook() — no guard code needed here
    // since the outer handleWebhook() wrapper covers all of them.

    /**
     * Handle invoice.payment_failed events.
     *
     * Marks the subscription as past_due and logs the event.
     * In production: trigger email notification to the practice owner.
     */
    protected function handleInvoicePaymentFailed(array $payload): Response
    {
        $invoice  = $payload['data']['object'];
        $customer = $invoice['customer'] ?? null;

        Log::warning('Stripe: invoice payment failed', [
            'customer'      => $customer,
            'invoice_id'    => $invoice['id'] ?? null,
            'amount_due'    => $invoice['amount_due'] ?? null,
            'attempt_count' => $invoice['attempt_count'] ?? null,
        ]);

        // Let Cashier update the subscription status via its own event
        // (it will set stripe_status = 'past_due' on the next subscription.updated webhook)

        return $this->successMethod();
    }

    /**
     * Extend the subscription created handler to also log and set up
     * plan-specific features if needed.
     */
    protected function handleCustomerSubscriptionCreated(array $payload): Response
    {
        Log::info('Stripe: subscription created', [
            'subscription_id' => $payload['data']['object']['id'] ?? null,
            'customer'        => $payload['data']['object']['customer'] ?? null,
            'status'          => $payload['data']['object']['status'] ?? null,
        ]);

        return parent::handleCustomerSubscriptionCreated($payload);
    }

    /**
     * Extend the subscription updated handler to log plan changes.
     */
    protected function handleCustomerSubscriptionUpdated(array $payload): Response
    {
        Log::info('Stripe: subscription updated', [
            'subscription_id' => $payload['data']['object']['id'] ?? null,
            'status'          => $payload['data']['object']['status'] ?? null,
        ]);

        return parent::handleCustomerSubscriptionUpdated($payload);
    }

    /**
     * Extend the subscription deleted handler to log cancellations.
     */
    protected function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        Log::info('Stripe: subscription cancelled', [
            'subscription_id' => $payload['data']['object']['id'] ?? null,
            'customer'        => $payload['data']['object']['customer'] ?? null,
        ]);

        return parent::handleCustomerSubscriptionDeleted($payload);
    }
}
