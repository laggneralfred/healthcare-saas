<?php

namespace App\Models\States\Appointment;

class Checkout extends AppointmentState
{
    public static string $name = 'checkout';

    public function label(): string
    {
        return 'Checkout';
    }
}
