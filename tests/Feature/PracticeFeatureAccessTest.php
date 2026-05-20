<?php

namespace Tests\Feature;

use App\Models\Practice;
use App\Models\SubscriptionPlan;
use App\Services\Billing\PracticeFeatureAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PracticeFeatureAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_starter_plus_and_clinic_feature_matrix(): void
    {
        $gate = app(PracticeFeatureAccess::class);

        $starter = Practice::factory()->make(['plan_tier' => Practice::PLAN_TIER_STARTER]);
        $plus = Practice::factory()->make(['plan_tier' => Practice::PLAN_TIER_PLUS]);
        $clinic = Practice::factory()->make(['plan_tier' => Practice::PLAN_TIER_CLINIC]);

        $this->assertFalse($gate->canUseAiFeatures($starter));
        $this->assertFalse($gate->canUseAdvancedFollowUp($starter));
        $this->assertFalse($gate->canUseAdvancedAutomation($starter));
        $this->assertFalse($gate->canUseMultiPractitionerScheduling($starter));

        $this->assertTrue($gate->canUseAiFeatures($plus));
        $this->assertTrue($gate->canUseAdvancedFollowUp($plus));
        $this->assertFalse($gate->canUseAdvancedAutomation($plus));
        $this->assertFalse($gate->canUseMultiPractitionerScheduling($plus));

        $this->assertTrue($gate->canUseAiFeatures($clinic));
        $this->assertTrue($gate->canUseAdvancedFollowUp($clinic));
        $this->assertTrue($gate->canUseAdvancedAutomation($clinic));
        $this->assertTrue($gate->canUseMultiPractitionerScheduling($clinic));
    }

    public function test_paid_clinic_subscription_keeps_plus_level_ai_access_even_if_plan_tier_is_starter(): void
    {
        $practice = Practice::factory()->create(['plan_tier' => Practice::PLAN_TIER_STARTER]);

        SubscriptionPlan::query()->create([
            'key' => 'clinic',
            'name' => 'Plus',
            'price_monthly' => 9900,
            'stripe_price_id' => 'price_clinic_test',
            'max_practitioners' => 5,
            'features' => [],
            'is_active' => true,
        ]);

        DB::table('subscriptions')->insert([
            'practice_id' => $practice->id,
            'type' => 'default',
            'stripe_id' => 'sub_clinic_'.$practice->id,
            'stripe_status' => 'active',
            'stripe_price' => 'price_clinic_test',
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $practice->unsetRelation('subscriptions');

        $gate = app(PracticeFeatureAccess::class);

        $this->assertSame(Practice::PLAN_TIER_PLUS, $gate->effectivePlanTier($practice));
        $this->assertTrue($gate->canUseAiFeatures($practice));
        $this->assertTrue($gate->canUseAdvancedFollowUp($practice));
        $this->assertFalse($gate->canUseAdvancedAutomation($practice));
    }
}
