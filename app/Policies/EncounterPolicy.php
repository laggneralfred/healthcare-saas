<?php

namespace App\Policies;

use App\Models\Encounter;
use App\Models\User;
use App\Policies\Concerns\AuthorizesPracticeRecords;

class EncounterPolicy
{
    use AuthorizesPracticeRecords;

    public function viewAny(User $user): bool
    {
        return $user->isPracticeSuperAdmin() || $user->canManageOperations() || $user->isPractitioner();
    }

    public function view(User $user, Encounter $encounter): bool
    {
        return $this->canManagePracticeRecord($user, $encounter)
            || $this->assignedEncounter($user, $encounter);
    }

    public function create(User $user): bool
    {
        return $user->isPracticeSuperAdmin()
            || ($user->practice_id !== null && ($user->canManageOperations() || $user->isPractitioner()));
    }

    public function update(User $user, Encounter $encounter): bool
    {
        return $this->canManagePracticeRecord($user, $encounter)
            || $this->assignedEncounter($user, $encounter);
    }

    public function delete(User $user, Encounter $encounter): bool
    {
        return $this->canManagePracticeRecord($user, $encounter);
    }
}
