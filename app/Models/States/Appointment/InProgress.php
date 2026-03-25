<?php

namespace App\Models\States\Appointment;

class InProgress extends AppointmentState
{
    public static string $name = 'in_progress';

    public function label(): string
    {
        return 'In Progress';
    }
}
