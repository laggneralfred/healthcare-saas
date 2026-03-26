<?php

namespace App\Observers;

use App\Models\Practice;
use App\Models\Practitioner;
use App\Services\PractitionerLimitGuard;

class PractitionerObserver
{
    public function __construct(private readonly PractitionerLimitGuard $guard)
    {
    }

    /**
     * Intercept every Practitioner creation at the ORM level.
     *
     * The guard's assertCanAddPractitioner() acquires a pessimistic write-lock
     * on the practice row before counting, serialising concurrent attempts for
     * the same practice.
     *
     * Enforcement is skipped when the practice has no active subscription plan
     * (currentLimit() returns null) — this covers initial setup, seeders, and
     * the Enterprise unlimited tier.
     *
     * @throws \App\Exceptions\PractitionerLimitExceededException
     */
    public function creating(Practitioner $practitioner): void
    {
        $practiceId = $practitioner->practice_id;

        if (! $practiceId) {
            return;
        }

        $practice = Practice::find($practiceId);

        if (! $practice) {
            return;
        }

        $this->guard->assertCanAddPractitioner($practice);
    }
}
