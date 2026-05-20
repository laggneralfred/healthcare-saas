<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Services\Billing\SubscriptionPlanCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_creates_known_subscription_plans_with_configured_stripe_prices(): void
    {
        config([
            'services.stripe.subscription_prices.solo' => 'price_solo_configured',
            'services.stripe.subscription_prices.clinic' => 'price_clinic_configured',
            'services.stripe.subscription_prices.enterprise' => 'price_enterprise_configured',
        ]);

        $this->artisan('billing:sync-stripe-prices')
            ->assertExitCode(0);

        $this->assertDatabaseHas('subscription_plans', [
            'key' => 'solo',
            'name' => 'Starter',
            'price_monthly' => 4900,
            'stripe_price_id' => 'price_solo_configured',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('subscription_plans', [
            'key' => 'clinic',
            'name' => 'Plus',
            'price_monthly' => 9900,
            'stripe_price_id' => 'price_clinic_configured',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('subscription_plans', [
            'key' => 'clinic',
            'price_monthly' => 9900,
            'stripe_price_id' => 'price_clinic_configured',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('subscription_plans', [
            'key' => 'enterprise',
            'name' => 'Clinic',
            'price_monthly' => 19900,
            'stripe_price_id' => 'price_enterprise_configured',
            'is_active' => true,
        ]);
    }

    public function test_sync_updates_existing_known_subscription_plan_price_ids(): void
    {
        SubscriptionPlan::create([
            'key' => 'solo',
            'name' => 'Starter',
            'price_monthly' => 4900,
            'stripe_price_id' => 'price_old',
            'max_practitioners' => 1,
            'features' => [],
            'is_active' => true,
        ]);

        config(['services.stripe.subscription_prices.solo' => 'price_new']);

        app(SubscriptionPlanCatalog::class)->syncConfiguredPlans();

        $this->assertDatabaseHas('subscription_plans', [
            'key' => 'solo',
            'stripe_price_id' => 'price_new',
        ]);
    }

    public function test_missing_config_leaves_new_plan_price_ids_null_without_crashing(): void
    {
        config([
            'services.stripe.subscription_prices.solo' => null,
            'services.stripe.subscription_prices.clinic' => null,
            'services.stripe.subscription_prices.enterprise' => null,
        ]);

        $this->artisan('billing:sync-stripe-prices')
            ->expectsOutputToContain('missing Stripe price ID')
            ->assertExitCode(0);

        $this->assertSame(3, SubscriptionPlan::count());
        $this->assertTrue(SubscriptionPlan::query()->get()->every(
            fn (SubscriptionPlan $plan): bool => $plan->stripe_price_id === null,
        ));
    }
}
