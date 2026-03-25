<?php

namespace App\Models\States\Appointment;

class Scheduled extends AppointmentState
{
    public static string $name = 'scheduled';

    public function label(): string
    {
        return 'Scheduled';
    }
}
