<?php

namespace Tests\Feature;

use App\Filament\Pages\BillingPage;
use App\Filament\Pages\BillingReadinessPage;
use App\Filament\Resources\SubscriptionPlans\SubscriptionPlanResource;
use App\Models\Practice;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Support\PracticeAccessRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BillingStripeReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PracticeAccessRoles::ensureRoles();
    }

    public function test_billing_page_blocks_missing_stripe_price_id(): void
    {
        [$practice, $user] = $this->practiceUser();
        SubscriptionPlan::create([
            'key' => 'solo',
            'name' => 'Solo Plan',
            'price_monthly' => 4900,
            'stripe_price_id' => null,
            'max_practitioners' => 1,
            'features' => [],
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $result = Livewire::test(BillingPage::class)
            ->call('subscribeToPlan', 'solo')
            ->instance();

        $this->assertNull($result->redirectTo ?? null);
        $this->assertNull($practice->fresh()->stripe_id);
    }

    public function test_configured_plan_reaches_subscription_attempt_path(): void
    {
        [, $user] = $this->practiceUser();
        SubscriptionPlan::create([
            'key' => 'solo',
            'name' => 'Solo Plan',
            'price_monthly' => 4900,
            'stripe_price_id' => 'price_solo_ready',
            'max_practitioners' => 1,
            'features' => [],
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $page = new class extends BillingPage {
            public bool $attempted = false;

            protected function attemptSubscription(\App\Models\Practice $practice, SubscriptionPlan $plan): mixed
            {
                $this->attempted = true;

                return 'checkout-attempted';
            }
        };

        $this->assertSame('checkout-attempted', $page->subscribeToPlan('solo'));
        $this->assertTrue($page->attempted);
    }

    public function test_normal_practice_users_cannot_access_subscription_plans_or_billing_readiness(): void
    {
        [, $user] = $this->practiceUser();

        $this->actingAs($user);

        $this->assertFalse(SubscriptionPlanResource::canAccess());
        $this->assertFalse(BillingReadinessPage::canAccess());
    }

    public function test_super_admin_can_view_billing_readiness_and_subscription_plans(): void
    {
        $superAdmin = User::factory()->create(['practice_id' => null]);
        $superAdmin->assignRole(User::ROLE_OWNER);

        SubscriptionPlan::create([
            'key' => 'solo',
            'name' => 'Solo Plan',
            'price_monthly' => 4900,
            'stripe_price_id' => 'price_solo_ready',
            'max_practitioners' => 1,
            'features' => [],
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin);

        $this->assertTrue(SubscriptionPlanResource::canAccess());
        $this->assertTrue(BillingReadinessPage::canAccess());

        Livewire::test(BillingReadinessPage::class)
            ->assertSee('Billing Readiness')
            ->assertSee('Stripe public key configured')
            ->assertSee('Subscription Plans')
            ->assertSee('Solo Plan');
    }

    public function test_billing_readiness_does_not_expose_secret_values(): void
    {
        $superAdmin = User::factory()->create(['practice_id' => null]);
        $superAdmin->assignRole(User::ROLE_OWNER);

        config([
            'services.stripe.public_key' => 'pk_test_public_value',
            'services.stripe.secret_key' => 'sk_test_do_not_show',
            'services.stripe.webhook_secret' => 'whsec_do_not_show',
            'services.stripe.subscription_prices.solo' => 'price_solo_ready',
            'services.stripe.subscription_prices.clinic' => 'price_clinic_ready',
            'services.stripe.subscription_prices.enterprise' => 'price_enterprise_ready',
        ]);

        $this->actingAs($superAdmin);

        Livewire::test(BillingReadinessPage::class)
            ->assertSee('Stripe secret configured')
            ->assertSee('Stripe webhook secret configured')
            ->assertDontSee('sk_test_do_not_show')
            ->assertDontSee('whsec_do_not_show')
            ->assertDontSee('pk_test_public_value');
    }

    private function practiceUser(): array
    {
        $practice = Practice::factory()->create();
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $user->assignRole(User::ROLE_OWNER);

        return [$practice, $user];
    }
}
