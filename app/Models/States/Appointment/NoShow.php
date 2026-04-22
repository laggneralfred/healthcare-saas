<?php

namespace App\Models\States\Appointment;

class NoShow extends AppointmentState
{
    public static string $name = 'no_show';

    public function label(): string
    {
        return 'No Show';
    }
}
