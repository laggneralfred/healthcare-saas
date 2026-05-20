<?php

namespace Tests\Feature;

use App\Models\Practice;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PracticePlanTierTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_practices_default_to_starter_plan_tier(): void
    {
        $practice = Practice::factory()->create();

        $this->assertSame(Practice::PLAN_TIER_STARTER, $practice->fresh()->plan_tier);
        $this->assertSame(Practice::PLAN_TIER_STARTER, $practice->planTier());
        $this->assertTrue($practice->isStarterPlan());
        $this->assertTrue($practice->hasFreeStarterAccess());
    }

    public function test_plan_tier_helper_methods_match_supported_values(): void
    {
        $starterPractice = Practice::factory()->make(['plan_tier' => 'starter']);
        $plusPractice = Practice::factory()->make(['plan_tier' => 'plus']);
        $clinicPractice = Practice::factory()->make(['plan_tier' => 'clinic']);
        $invalidPractice = Practice::factory()->make(['plan_tier' => 'invalid-tier']);

        $this->assertTrue($starterPractice->isStarterPlan());
        $this->assertFalse($starterPractice->isPlusPlan());
        $this->assertFalse($starterPractice->isClinicPlan());

        $this->assertTrue($plusPractice->isPlusPlan());
        $this->assertFalse($plusPractice->isStarterPlan());
        $this->assertFalse($plusPractice->isClinicPlan());

        $this->assertTrue($clinicPractice->isClinicPlan());
        $this->assertFalse($clinicPractice->isStarterPlan());
        $this->assertFalse($clinicPractice->isPlusPlan());

        $this->assertSame(Practice::PLAN_TIER_STARTER, $invalidPractice->planTier());
        $this->assertTrue($invalidPractice->isStarterPlan());
    }

    public function test_current_subscription_behavior_is_unchanged_by_plan_tier_field(): void
    {
        $practice = Practice::factory()->create(['plan_tier' => Practice::PLAN_TIER_CLINIC]);

        $this->assertNull($practice->currentPlan());

        SubscriptionPlan::query()->create([
            'key' => 'solo',
            'name' => 'Starter',
            'price_monthly' => 4900,
            'stripe_price_id' => 'price_solo_test',
            'max_practitioners' => 1,
            'features' => [],
            'is_active' => true,
        ]);

        DB::table('subscriptions')->insert([
            'practice_id' => $practice->id,
            'type' => 'default',
            'stripe_id' => 'sub_solo_'.$practice->id,
            'stripe_status' => 'active',
            'stripe_price' => 'price_solo_test',
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $practice->unsetRelation('subscriptions');

        $resolvedPlan = $practice->currentPlan();

        $this->assertNotNull($resolvedPlan);
        $this->assertSame('solo', $resolvedPlan->key);
        $this->assertSame('Starter', $resolvedPlan->name);
    }
}
