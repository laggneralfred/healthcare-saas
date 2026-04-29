<?php

namespace App\Policies;

use App\Models\Practitioner;
use App\Models\User;
use App\Policies\Concerns\AuthorizesPracticeRecords;

class PractitionerPolicy
{
    use AuthorizesPracticeRecords;

    public function viewAny(User $user): bool
    {
        return $user->isPracticeSuperAdmin() || $user->canManageOperations() || $user->isPractitioner();
    }

    public function view(User $user, Practitioner $practitioner): bool
    {
        if ($this->canManagePracticeRecord($user, $practitioner)) {
            return true;
        }

        $userPractitioner = $this->practitionerFor($user);

        return $this->samePractice($user, $practitioner)
            && $userPractitioner !== null
            && (int) $userPractitioner->id === (int) $practitioner->id;
    }

    public function create(User $user): bool
    {
        return $user->isPracticeSuperAdmin() || $user->canManageOperations();
    }

    public function update(User $user, Practitioner $practitioner): bool
    {
        return $this->canManagePracticeRecord($user, $practitioner);
    }

    public function delete(User $user, Practitioner $practitioner): bool
    {
        return $this->canManagePracticeRecord($user, $practitioner);
    }
}
