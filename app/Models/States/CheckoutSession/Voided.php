<?php

namespace App\Models\States\CheckoutSession;

class Voided extends CheckoutSessionState
{
    public static string $name = 'void';

    public function label(): string
    {
        return 'Void';
    }
}
