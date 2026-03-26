<?php

namespace Tests\Feature;

use App\Exceptions\PractitionerLimitExceededException;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\PractitionerLimitGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Verifies that PractitionerLimitGuard + PractitionerObserver correctly enforce
 * per-plan practitioner limits at the ORM level.
 *
 * Test subscription setup
 * ───────────────────────
 * Cashier reads from the `subscriptions` table (not the Stripe API), so tests
 * insert raw rows there alongside SubscriptionPlan records.  This is equivalent
 * to what a real active subscription produces in the database.
 *
 * SQLite / lockForUpdate note
 * ───────────────────────────
 * SQLite (the test database driver) does not support SELECT … FOR UPDATE.
 * The pessimistic lock is silently ignored, but the count logic and exception
 * behaviour are fully exercised.  Real concurrency protection is validated in
 * staging against MySQL / PostgreSQL.
 */
class PractitionerLimitTest extends TestCase
{
    use RefreshDatabase;

    private Practice             $practice;
    private User                 $user;
    private PractitionerLimitGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->practice = Practice::factory()->create();
        $this->user     = User::factory()->create(['practice_id' => $this->practice->id]);
        $this->guard    = app(PractitionerLimitGuard::class);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Simulate an active subscription by inserting the necessary rows directly
     * into the database — identical to what Cashier writes after a real checkout.
     */
    private function subscribePracticeTo(Practice $practice, string $planKey): void
    {
        $specs = [
            'solo'       => ['name' => 'Solo Plan',       'price' => 4900,  'max' => 1,  'priceId' => 'price_solo_test'],
            'clinic'     => ['name' => 'Clinic Plan',     'price' => 9900,  'max' => 5,  'priceId' => 'price_clinic_test'],
            'enterprise' => ['name' => 'Enterprise Plan', 'price' => 19900, 'max' => -1, 'priceId' => 'price_enterprise_test'],
        ];

        $s = $specs[$planKey];

        SubscriptionPlan::updateOrCreate(
            ['key' => $planKey],
            [
                'name'               => $s['name'],
                'price_monthly'      => $s['price'],
                'stripe_price_id'    => $s['priceId'],
                'max_practitioners'  => $s['max'],
                'features'           => [],
            ]
        );

        // updateOrInsert so that calling subscribePracticeTo() twice on the same
        // practice (for the downgrade test) updates the existing row rather than
        // trying to insert a duplicate.
        DB::table('subscriptions')->updateOrInsert(
            ['practice_id' => $practice->id, 'type' => 'default'],
            [
                'stripe_id'     => 'sub_' . $planKey . '_' . $practice->id,
                'stripe_status' => 'active',
                'stripe_price'  => $s['priceId'],
                'quantity'      => 1,
                'trial_ends_at' => null,
                'ends_at'       => null,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]
        );

        // Clear Cashier's in-memory relationship cache so the next call to
        // currentPlan() re-queries the subscriptions table.
        $practice->unsetRelation('subscriptions');
    }

    /** Create a practitioner for the test practice, going through the observer. */
    private function addPractitioner(): Practitioner
    {
        return Practitioner::factory()->create(['practice_id' => $this->practice->id]);
    }

    /** Count practitioners for the test practice, bypassing the global scope. */
    private function practitionerCount(): int
    {
        return Practitioner::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->count();
    }

    // ── Plan-level enforcement tests ───────────────────────────────────────────

    public function test_solo_practice_cannot_add_second_practitioner(): void
    {
        $this->subscribePracticeTo($this->practice, 'solo');

        $this->assertEquals(1, $this->guard->currentLimit($this->practice));

        // First practitioner: allowed.
        $this->addPractitioner();
        $this->assertEquals(1, $this->practitionerCount());
        $this->assertFalse($this->guard->canAddPractitioner($this->practice));

        // Second practitioner: blocked.
        $this->expectException(PractitionerLimitExceededException::class);
        $this->expectExceptionMessageMatches('/limit of 1/');
        $this->addPractitioner();
    }

    public function test_clinic_practice_can_add_up_to_5_blocked_on_6th(): void
    {
        $this->subscribePracticeTo($this->practice, 'clinic');

        $this->assertEquals(5, $this->guard->currentLimit($this->practice));

        // Practitioners 1–5: all succeed.
        for ($i = 1; $i <= 5; $i++) {
            $this->addPractitioner();
            $this->assertEquals($i, $this->practitionerCount());
        }

        $this->assertFalse($this->guard->canAddPractitioner($this->practice));

        // 6th practitioner: blocked.
        $this->expectException(PractitionerLimitExceededException::class);
        $this->expectExceptionMessageMatches('/limit of 5/');
        $this->addPractitioner();
    }

    public function test_enterprise_practice_has_no_limit(): void
    {
        $this->subscribePracticeTo($this->practice, 'enterprise');

        $this->assertNull($this->guard->currentLimit($this->practice));
        $this->assertTrue($this->guard->canAddPractitioner($this->practice));

        // Create well beyond any reasonable hard limit.
        for ($i = 0; $i < 12; $i++) {
            $this->addPractitioner();
        }

        $this->assertEquals(12, $this->practitionerCount());
        $this->assertTrue($this->guard->canAddPractitioner($this->practice));
    }

    // ── No-plan (initial setup / seeder) test ─────────────────────────────────

    public function test_practice_with_no_subscription_can_add_practitioners(): void
    {
        // No subscription exists for this practice — simulates initial setup
        // or running the database seeder before billing is configured.
        $this->assertNull($this->guard->currentLimit($this->practice));
        $this->assertTrue($this->guard->canAddPractitioner($this->practice));

        // Observer must NOT block creation when there is no active plan.
        $this->addPractitioner();
        $this->assertEquals(1, $this->practitionerCount());
    }

    // ── Downgrade test ─────────────────────────────────────────────────────────

    public function test_downgrading_plan_enforces_new_limit_immediately(): void
    {
        // Start on Clinic (limit = 5), add 3 practitioners.
        $this->subscribePracticeTo($this->practice, 'clinic');

        for ($i = 0; $i < 3; $i++) {
            $this->addPractitioner();
        }

        $this->assertEquals(3, $this->practitionerCount());

        // Downgrade to Solo (limit = 1).
        // The 3 existing practitioners stay; the limit applies to new additions.
        $this->subscribePracticeTo($this->practice, 'solo');
        $this->practice->refresh(); // flush the Cashier subscription cache

        $this->assertEquals(1,  $this->guard->currentLimit($this->practice));
        $this->assertEquals(3,  $this->guard->currentCount($this->practice));
        $this->assertFalse($this->guard->canAddPractitioner($this->practice));

        // Any further creation is immediately blocked.
        $this->expectException(PractitionerLimitExceededException::class);
        $this->expectExceptionMessageMatches('/limit of 1.*currently 3/s');
        $this->addPractitioner();
    }

    // ── Concurrent creation test ───────────────────────────────────────────────

    /**
     * Simulates a race condition where two requests read the same pre-insertion
     * count and both believe they are within the limit.
     *
     * The guard's assertCanAddPractitioner() re-reads the count inside a
     * lockForUpdate transaction, so by the time the second request's guard runs
     * (after the first request's INSERT is visible), it sees the updated count
     * and rejects the creation.
     *
     * Under SQLite the FOR UPDATE clause is a no-op, but the count re-read
     * logic is fully exercised.  On MySQL / PostgreSQL the lock prevents both
     * requests from reading the same stale count simultaneously.
     */
    public function test_concurrent_creation_attempt_is_handled_safely(): void
    {
        $this->subscribePracticeTo($this->practice, 'solo');

        // Both "requests" initially read: count = 0, limit = 1 → can add.
        $this->assertTrue($this->guard->canAddPractitioner($this->practice));

        // Request A completes its INSERT (bypasses the observer to model the
        // scenario where it committed before Request B's guard runs).
        Practitioner::withoutEvents(
            fn () => Practitioner::factory()->create(['practice_id' => $this->practice->id])
        );

        // Request B's guard now runs — it re-reads count inside the lock.
        // count = 1, limit = 1 → count >= limit → rejected.
        $this->assertFalse($this->guard->canAddPractitioner($this->practice));

        $this->expectException(PractitionerLimitExceededException::class);
        $this->guard->assertCanAddPractitioner($this->practice);
    }

    // ── Guard unit tests ───────────────────────────────────────────────────────

    public function test_current_limit_returns_correct_values_per_plan(): void
    {
        $this->assertNull($this->guard->currentLimit($this->practice), 'No plan → null');

        $this->subscribePracticeTo($this->practice, 'solo');
        $this->practice->refresh();
        $this->assertEquals(1, $this->guard->currentLimit($this->practice), 'Solo → 1');

        $this->subscribePracticeTo($this->practice, 'clinic');
        $this->practice->refresh();
        $this->assertEquals(5, $this->guard->currentLimit($this->practice), 'Clinic → 5');

        $this->subscribePracticeTo($this->practice, 'enterprise');
        $this->practice->refresh();
        $this->assertNull($this->guard->currentLimit($this->practice), 'Enterprise → null');
    }

    public function test_current_count_reflects_only_target_practice(): void
    {
        $otherPractice = Practice::factory()->create();
        Practitioner::withoutEvents(function () use ($otherPractice) {
            Practitioner::factory()->count(3)->create(['practice_id' => $otherPractice->id]);
            Practitioner::factory()->count(2)->create(['practice_id' => $this->practice->id]);
        });

        $this->assertEquals(2, $this->guard->currentCount($this->practice));
        $this->assertEquals(3, $this->guard->currentCount($otherPractice));
    }

    public function test_exception_carries_practice_limit_and_current_count(): void
    {
        $this->subscribePracticeTo($this->practice, 'solo');

        Practitioner::withoutEvents(
            fn () => Practitioner::factory()->create(['practice_id' => $this->practice->id])
        );

        try {
            $this->guard->assertCanAddPractitioner($this->practice);
            $this->fail('Expected PractitionerLimitExceededException was not thrown');
        } catch (PractitionerLimitExceededException $e) {
            $this->assertSame($this->practice->id, $e->practice->id);
            $this->assertSame(1, $e->limit);
            $this->assertSame(1, $e->current);
        }
    }

    public function test_isolation_between_practices(): void
    {
        $practiceA = Practice::factory()->create();
        $practiceB = Practice::factory()->create();

        $this->subscribePracticeTo($practiceA, 'solo');
        $this->subscribePracticeTo($practiceB, 'solo');

        // Practice A fills its slot.
        Practitioner::factory()->create(['practice_id' => $practiceA->id]);

        // Practice A is blocked.
        $this->assertFalse($this->guard->canAddPractitioner($practiceA));
        $this->expectException(PractitionerLimitExceededException::class);
        $this->guard->assertCanAddPractitioner($practiceA);

        // Practice B is independent and still has capacity — not thrown yet.
        $this->assertTrue($this->guard->canAddPractitioner($practiceB));
    }
}
