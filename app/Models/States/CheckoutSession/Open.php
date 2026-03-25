<?php

namespace App\Models\States\CheckoutSession;

class Open extends CheckoutSessionState
{
    public static string $name = 'open';

    public function label(): string
    {
        return 'Open';
    }
}
