<?php

namespace App\Models\States\CheckoutSession;

class Paid extends CheckoutSessionState
{
    public static string $name = 'paid';

    public function label(): string
    {
        return 'Paid';
    }
}
