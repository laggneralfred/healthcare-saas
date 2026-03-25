<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;

class StripeWebhookController extends CashierWebhookController
{
    /**
     * Handle invoice.payment_failed events.
     *
     * Marks the subscription as past_due and logs the event.
     * In production: trigger email notification to the practice owner.
     */
    protected function handleInvoicePaymentFailed(array $payload): \Symfony\Component\HttpFoundation\Response
    {
        $invoice  = $payload['data']['object'];
        $customer = $invoice['customer'] ?? null;

        Log::warning('Stripe: invoice payment failed', [
            'customer'       => $customer,
            'invoice_id'     => $invoice['id'] ?? null,
            'amount_due'     => $invoice['amount_due'] ?? null,
            'attempt_count'  => $invoice['attempt_count'] ?? null,
        ]);

        // Let Cashier update the subscription status via its own event
        // (it will set stripe_status = 'past_due' on the next subscription.updated webhook)

        return $this->successMethod();
    }

    /**
     * Extend the subscription created handler to also log and set up
     * plan-specific features if needed.
     */
    protected function handleCustomerSubscriptionCreated(array $payload): \Symfony\Component\HttpFoundation\Response
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
    protected function handleCustomerSubscriptionUpdated(array $payload): \Symfony\Component\HttpFoundation\Response
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
    protected function handleCustomerSubscriptionDeleted(array $payload): \Symfony\Component\HttpFoundation\Response
    {
        Log::info('Stripe: subscription cancelled', [
            'subscription_id' => $payload['data']['object']['id'] ?? null,
            'customer'        => $payload['data']['object']['customer'] ?? null,
        ]);

        return parent::handleCustomerSubscriptionDeleted($payload);
    }
}
