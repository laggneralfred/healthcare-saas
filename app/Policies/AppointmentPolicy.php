<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use App\Policies\Concerns\AuthorizesPracticeRecords;

class AppointmentPolicy
{
    use AuthorizesPracticeRecords;

    public function viewAny(User $user): bool
    {
        return $user->isPracticeSuperAdmin() || $user->canManageOperations() || $user->isPractitioner();
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $this->canManagePracticeRecord($user, $appointment)
            || $this->assignedAppointment($user, $appointment);
    }

    public function create(User $user): bool
    {
        return $user->isPracticeSuperAdmin() || $user->canManageOperations();
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $this->canManagePracticeRecord($user, $appointment);
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $this->canManagePracticeRecord($user, $appointment);
    }
}
