<?php

namespace Tests\Feature;

use App\Models\WebhookEvent;
use App\Services\StripeWebhookGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Verifies that the StripeWebhookController + StripeWebhookGuard combination
 * enforces idempotency: a Stripe event that is delivered more than once is
 * only processed once, and subsequent deliveries are acknowledged (200) without
 * re-running any business logic.
 *
 * Stripe signature verification is bypassed in the testing environment by
 * Cashier (no STRIPE_WEBHOOK_SECRET is set in phpunit.xml).
 */
class WebhookIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Build a minimal Stripe-style event payload.
     *
     * Uses invoice.payment_failed because our handler for that event is
     * side-effect-free (log only) — no DB writes from business logic, so the
     * only DB change we observe is the guard's own audit row.
     */
    private function invoicePaymentFailedPayload(string $eventId): array
    {
        return [
            'id'   => $eventId,
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id'            => 'in_test_' . Str::random(10),
                    'customer'      => 'cus_test_' . Str::random(10),
                    'amount_due'    => 9500,
                    'attempt_count' => 1,
                ],
            ],
        ];
    }

    private function postWebhook(array $payload): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/stripe/webhook', $payload);
    }

    // ── Core idempotency test ──────────────────────────────────────────────────

    /**
     * Sending the same Stripe event twice must:
     *   - Return 200 for both deliveries.
     *   - Store exactly one audit row in webhook_events.
     *   - Mark the event as processed after the first delivery.
     *   - Not update processed_at on the second delivery.
     */
    public function test_duplicate_webhook_event_is_silently_ignored(): void
    {
        $eventId = 'evt_test_' . Str::random(24);
        $payload = $this->invoicePaymentFailedPayload($eventId);

        // ── First delivery ─────────────────────────────────────────────────────
        $first = $this->postWebhook($payload);
        $first->assertOk();

        $this->assertDatabaseCount('webhook_events', 1);
        $this->assertDatabaseHas('webhook_events', [
            'stripe_event_id' => $eventId,
            'type'            => 'invoice.payment_failed',
        ]);

        $eventAfterFirst = WebhookEvent::where('stripe_event_id', $eventId)->sole();
        $this->assertNotNull($eventAfterFirst->processed_at, 'Event must be marked processed after first delivery');
        $this->assertNull($eventAfterFirst->failed_at);
        $this->assertNull($eventAfterFirst->error);

        $processedAt = $eventAfterFirst->processed_at;

        // ── Second delivery (duplicate) ────────────────────────────────────────
        $second = $this->postWebhook($payload);
        $second->assertOk();

        // Still only one row — no duplicate insert.
        $this->assertDatabaseCount('webhook_events', 1);

        $eventAfterSecond = WebhookEvent::where('stripe_event_id', $eventId)->sole();

        // processed_at must not have changed — second delivery did not re-process.
        $this->assertTrue(
            $processedAt->eq($eventAfterSecond->processed_at),
            'processed_at must not be updated on duplicate delivery'
        );
    }

    // ── Distinct events are each processed once ────────────────────────────────

    public function test_two_distinct_events_are_each_processed(): void
    {
        $idA = 'evt_test_A_' . Str::random(20);
        $idB = 'evt_test_B_' . Str::random(20);

        $this->postWebhook($this->invoicePaymentFailedPayload($idA))->assertOk();
        $this->postWebhook($this->invoicePaymentFailedPayload($idB))->assertOk();

        $this->assertDatabaseCount('webhook_events', 2);

        $this->assertDatabaseHas('webhook_events', ['stripe_event_id' => $idA]);
        $this->assertDatabaseHas('webhook_events', ['stripe_event_id' => $idB]);

        $this->assertNotNull(WebhookEvent::where('stripe_event_id', $idA)->value('processed_at'));
        $this->assertNotNull(WebhookEvent::where('stripe_event_id', $idB)->value('processed_at'));
    }

    // ── Guard unit tests ───────────────────────────────────────────────────────

    public function test_guard_already_processed_returns_false_for_unknown_event(): void
    {
        $guard = app(StripeWebhookGuard::class);

        $this->assertFalse($guard->alreadyProcessed('evt_nonexistent'));
    }

    public function test_guard_already_processed_returns_false_when_only_failed(): void
    {
        $eventId = 'evt_test_' . Str::random(24);

        WebhookEvent::create([
            'stripe_event_id' => $eventId,
            'type'            => 'invoice.payment_failed',
            'payload'         => [],
            // processed_at deliberately left null — simulates a previous failure
            'failed_at'       => now(),
            'error'           => 'Something went wrong',
        ]);

        $guard = app(StripeWebhookGuard::class);

        $this->assertFalse(
            $guard->alreadyProcessed($eventId),
            'A previously failed event must not be treated as processed'
        );
    }

    public function test_guard_mark_processed_stamps_timestamp(): void
    {
        $eventId = 'evt_test_' . Str::random(24);

        WebhookEvent::create([
            'stripe_event_id' => $eventId,
            'type'            => 'invoice.payment_failed',
            'payload'         => [],
        ]);

        $guard = app(StripeWebhookGuard::class);
        $guard->markProcessed($eventId);

        $event = WebhookEvent::where('stripe_event_id', $eventId)->sole();
        $this->assertNotNull($event->processed_at);
        $this->assertNull($event->failed_at);
        $this->assertNull($event->error);

        $this->assertTrue($guard->alreadyProcessed($eventId));
    }

    public function test_guard_mark_failed_stamps_error_and_leaves_processed_at_null(): void
    {
        $eventId = 'evt_test_' . Str::random(24);

        WebhookEvent::create([
            'stripe_event_id' => $eventId,
            'type'            => 'invoice.payment_failed',
            'payload'         => [],
        ]);

        $guard = app(StripeWebhookGuard::class);
        $guard->markFailed($eventId, 'Stripe API timeout');

        $event = WebhookEvent::where('stripe_event_id', $eventId)->sole();
        $this->assertNull($event->processed_at);
        $this->assertNotNull($event->failed_at);
        $this->assertSame('Stripe API timeout', $event->error);

        // A failed event must still be re-processable.
        $this->assertFalse($guard->alreadyProcessed($eventId));
    }

    public function test_guard_mark_processed_clears_prior_failure(): void
    {
        $eventId = 'evt_test_' . Str::random(24);

        WebhookEvent::create([
            'stripe_event_id' => $eventId,
            'type'            => 'invoice.payment_failed',
            'payload'         => [],
            'failed_at'       => now()->subMinutes(5),
            'error'           => 'transient error',
        ]);

        $guard = app(StripeWebhookGuard::class);
        $guard->markProcessed($eventId);

        $event = WebhookEvent::where('stripe_event_id', $eventId)->sole();
        $this->assertNotNull($event->processed_at);
        $this->assertNull($event->failed_at);
        $this->assertNull($event->error);
    }

    // ── Payload and type are persisted ────────────────────────────────────────

    public function test_webhook_event_stores_type_and_payload(): void
    {
        $eventId = 'evt_test_' . Str::random(24);
        $payload = $this->invoicePaymentFailedPayload($eventId);

        $this->postWebhook($payload)->assertOk();

        $event = WebhookEvent::where('stripe_event_id', $eventId)->sole();

        $this->assertSame('invoice.payment_failed', $event->type);
        $this->assertSame($eventId, $event->payload['id']);
        $this->assertSame('invoice.payment_failed', $event->payload['type']);
    }
}
