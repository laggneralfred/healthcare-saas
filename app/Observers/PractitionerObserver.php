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
     * Enforcement is skipped only when currentLimit() resolves to unlimited
     * (null), such as Enterprise and non-starter tiers without an active
     * subscription.
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
