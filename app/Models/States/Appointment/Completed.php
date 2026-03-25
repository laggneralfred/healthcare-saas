<?php

namespace App\Models\States\Appointment;

class Completed extends AppointmentState
{
    public static string $name = 'completed';

    public function label(): string
    {
        return 'Completed';
    }
}
