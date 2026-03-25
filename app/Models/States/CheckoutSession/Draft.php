<?php

namespace App\Models\States\CheckoutSession;

class Draft extends CheckoutSessionState
{
    public static string $name = 'draft';

    public function label(): string
    {
        return 'Draft';
    }
}
