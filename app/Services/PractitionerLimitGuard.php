<?php

namespace App\Services;

use App\Exceptions\PractitionerLimitExceededException;
use App\Models\Practice;
use App\Models\Practitioner;
use Illuminate\Support\Facades\DB;

class PractitionerLimitGuard
{
    /**
     * The max_practitioners limit imposed by the practice's active subscription plan.
     *
     * Returns null for unlimited (Enterprise plan or no active subscription — the
     * "no subscription" case allows initial data setup before billing is configured).
     * Returns a positive integer for Solo (1) and Clinic (5).
     */
    public function currentLimit(Practice $practice): int|null
    {
        $plan = $practice->currentPlan();

        if ($plan === null) {
            return null; // No active subscription — no enforcement
        }

        if ($plan->max_practitioners === -1) {
            return null; // Enterprise — unlimited
        }

        return $plan->max_practitioners;
    }

    /**
     * The current number of practitioners belonging to the practice.
     *
     * Bypasses the BelongsToPractice global scope so the count is always
     * accurate regardless of which user is authenticated.
     */
    public function currentCount(Practice $practice): int
    {
        return Practitioner::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->count();
    }

    /**
     * Non-locking check suitable for UI display (e.g. enabling/disabling buttons).
     *
     * NOT safe against race conditions — two concurrent requests can both read
     * "under the limit" simultaneously.  Use assertCanAddPractitioner() to enforce
     * the limit at insertion time.
     */
    public function canAddPractitioner(Practice $practice): bool
    {
        $limit = $this->currentLimit($practice);

        if ($limit === null) {
            return true;
        }

        return $this->currentCount($practice) < $limit;
    }

    /**
     * Race-condition-safe enforcement.
     *
     * Acquires a pessimistic write-lock on the practice row so that all concurrent
     * creation attempts are serialised.  While the lock is held, no other
     * transaction can re-read or modify the same practice row, ensuring the count
     * seen here reflects the committed state of the database.
     *
     * Lock scope: covers the count check only.  The subsequent INSERT (from
     * Practitioner::create()) happens immediately after this transaction commits.
     * For full atomicity (lock + INSERT in one transaction) wrap the call site in
     * an outer DB::transaction() and bypass this method's internal transaction via
     * the CreatePractitionerAction pattern.
     *
     * SQLite note: lockForUpdate() is silently ignored by SQLite (used in tests).
     * The count logic is still correct; only the mutual-exclusion guarantee is
     * absent in the test environment.  Real concurrency protection is verified
     * against MySQL / PostgreSQL in staging.
     *
     * @throws PractitionerLimitExceededException
     */
    public function assertCanAddPractitioner(Practice $practice): void
    {
        DB::transaction(function () use ($practice) {
            // Acquire an exclusive row-level lock on the practice record.
            // Any concurrent transaction that also calls lockForUpdate() on the
            // same row will block here until this transaction commits.
            Practice::lockForUpdate()->findOrFail($practice->id);

            $limit = $this->currentLimit($practice);

            if ($limit === null) {
                return; // Unlimited — nothing to enforce
            }

            $count = Practitioner::withoutPracticeScope()
                ->where('practice_id', $practice->id)
                ->count();

            if ($count >= $limit) {
                throw new PractitionerLimitExceededException($practice, $limit, $count);
            }
        });
    }
}
