<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;
use App\Policies\Concerns\AuthorizesPracticeRecords;

class PatientPolicy
{
    use AuthorizesPracticeRecords;

    public function viewAny(User $user): bool
    {
        return $user->isPracticeSuperAdmin() || $user->canManageOperations() || $user->isPractitioner();
    }

    public function view(User $user, Patient $patient): bool
    {
        return $this->canManagePracticeRecord($user, $patient)
            || $this->assignedPatient($user, $patient);
    }

    public function create(User $user): bool
    {
        return $user->isPracticeSuperAdmin() || $user->canManageOperations();
    }

    public function update(User $user, Patient $patient): bool
    {
        return $this->canManagePracticeRecord($user, $patient);
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $this->canManagePracticeRecord($user, $patient);
    }
}
