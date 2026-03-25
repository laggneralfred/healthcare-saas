<?php

namespace App\Models\States\CheckoutSession;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class CheckoutSessionState extends State
{
    abstract public function label(): string;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Draft::class)
            ->allowTransition(Draft::class, Open::class)
            ->allowTransition(Open::class, Paid::class)
            ->allowTransition(Open::class, PaymentDue::class)
            ->allowTransition(Open::class, Voided::class)
            ->allowTransition(PaymentDue::class, Paid::class)
            ->allowTransition(PaymentDue::class, Voided::class);
    }
}
