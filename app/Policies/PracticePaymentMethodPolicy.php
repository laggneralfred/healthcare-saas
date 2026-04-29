<?php

namespace App\Policies;

use App\Models\PracticePaymentMethod;
use App\Models\User;
use App\Policies\Concerns\AuthorizesPracticeRecords;

class PracticePaymentMethodPolicy
{
    use AuthorizesPracticeRecords;

    public function viewAny(User $user): bool
    {
        return $user->isPracticeSuperAdmin() || $user->canManageOperations();
    }

    public function view(User $user, PracticePaymentMethod $practicePaymentMethod): bool
    {
        return $this->canManagePracticeRecord($user, $practicePaymentMethod);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, PracticePaymentMethod $practicePaymentMethod): bool
    {
        return $this->canManagePracticeRecord($user, $practicePaymentMethod);
    }

    public function delete(User $user, PracticePaymentMethod $practicePaymentMethod): bool
    {
        return false;
    }
}
