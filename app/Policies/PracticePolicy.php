<?php

namespace App\Policies;

use App\Models\Practice;
use App\Models\User;

class PracticePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPracticeSuperAdmin() || $user->canManageOperations();
    }

    public function view(User $user, Practice $practice): bool
    {
        return $user->isPracticeSuperAdmin()
            || ((int) $user->practice_id === (int) $practice->id && $user->canManageOperations());
    }

    public function create(User $user): bool
    {
        return $user->isPracticeSuperAdmin();
    }

    public function update(User $user, Practice $practice): bool
    {
        return $this->view($user, $practice);
    }

    public function delete(User $user, Practice $practice): bool
    {
        return $user->isPracticeSuperAdmin();
    }
}
