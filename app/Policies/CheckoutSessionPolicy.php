<?php

namespace App\Policies;

use App\Models\CheckoutSession;
use App\Models\User;
use App\Policies\Concerns\AuthorizesPracticeRecords;

class CheckoutSessionPolicy
{
    use AuthorizesPracticeRecords;

    public function viewAny(User $user): bool
    {
        return $user->isPracticeSuperAdmin() || $user->canManageOperations();
    }

    public function view(User $user, CheckoutSession $checkoutSession): bool
    {
        return $this->canManagePracticeRecord($user, $checkoutSession);
    }

    public function create(User $user): bool
    {
        return $user->isPracticeSuperAdmin() || $user->canManageOperations();
    }

    public function update(User $user, CheckoutSession $checkoutSession): bool
    {
        return $this->canManagePracticeRecord($user, $checkoutSession);
    }

    public function delete(User $user, CheckoutSession $checkoutSession): bool
    {
        return $this->canManagePracticeRecord($user, $checkoutSession);
    }
}
