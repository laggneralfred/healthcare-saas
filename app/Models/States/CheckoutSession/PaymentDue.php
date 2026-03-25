<?php

namespace App\Models\States\CheckoutSession;

class PaymentDue extends CheckoutSessionState
{
    public static string $name = 'payment_due';

    public function label(): string
    {
        return 'Payment Due';
    }
}
