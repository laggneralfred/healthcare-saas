<?php

namespace App\Models\States\Appointment;

class Closed extends AppointmentState
{
    public static string $name = 'closed';

    public function label(): string
    {
        return 'Closed';
    }
}
