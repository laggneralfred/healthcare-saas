<?php

namespace App\Policies;

use App\Models\MedicalHistory;
use App\Models\User;
use App\Policies\Concerns\AuthorizesPracticeRecords;

class MedicalHistoryPolicy
{
    use AuthorizesPracticeRecords;

    public function viewAny(User $user): bool
    {
        return $user->isPracticeSuperAdmin() || $user->canManageOperations() || $user->isPractitioner();
    }

    public function view(User $user, MedicalHistory $medicalHistory): bool
    {
        return $this->canManagePracticeRecord($user, $medicalHistory)
            || $this->assignedMedicalHistory($user, $medicalHistory);
    }

    public function create(User $user): bool
    {
        return $user->isPracticeSuperAdmin() || $user->canManageOperations();
    }

    public function update(User $user, MedicalHistory $medicalHistory): bool
    {
        return $this->canManagePracticeRecord($user, $medicalHistory);
    }

    public function delete(User $user, MedicalHistory $medicalHistory): bool
    {
        return $this->canManagePracticeRecord($user, $medicalHistory);
    }
}
