<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function manageRoles(User $user, User $target): bool
    {
        if ($user->isPracticeSuperAdmin()) {
            return true;
        }

        return $user->isOwner()
            && (int) $user->practice_id === (int) $target->practice_id;
    }

    public function manageOwnerRole(User $user, User $target): bool
    {
        return $user->isPracticeSuperAdmin()
            || ($user->isOwner() && (int) $user->practice_id === (int) $target->practice_id);
    }
}
