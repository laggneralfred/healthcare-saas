<?php

namespace App\Models\States\Appointment;

class Cancelled extends AppointmentState
{
    public static string $name = 'cancelled';

    public function label(): string
    {
        return 'Cancelled';
    }
}
