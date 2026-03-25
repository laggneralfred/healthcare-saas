<?php

namespace App\Models\States\Appointment;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class AppointmentState extends State
{
    abstract public function label(): string;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Scheduled::class)
            ->allowTransition(Scheduled::class, InProgress::class)
            ->allowTransition(InProgress::class, Completed::class)
            ->allowTransition(Completed::class, Closed::class)
            ->allowTransition(Closed::class, Checkout::class);
    }
}
