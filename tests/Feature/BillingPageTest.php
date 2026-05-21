<?php

namespace Tests\Feature;

use App\Filament\Pages\BillingPage;
use App\Models\Practice;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Billing\StripeSubscriptionSyncService;
use App\Support\PracticeAccessRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\SubscriptionBuilder;
use Livewire\Livewire;
use ReflectionProperty;
use Tests\TestCase;

class BillingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PracticeAccessRoles::ensureRoles();
    }

    public function test_future_app_trial_is_carried_to_stripe_subscription_builder(): void
    {
        $practice = Practice::factory()->create([
            'trial_ends_at' => now()->addDays(20)->setSecond(0),
        ]);
        $plan = $this->plan();
        $page = new BillingPageTrialHarness();

        $builder = $page->applyTrial(
            $practice->newSubscription('default', $plan->stripe_price_id),
            $practice,
        );

        $this->assertSame(
            $practice->trial_ends_at->timestamp,
            $this->trialExpiresFor($builder)?->timestamp,
        );
    }

    public function test_expired_or_missing_app_trial_creates_normal_checkout_builder(): void
    {
        $plan = $this->plan();
        $page = new BillingPageTrialHarness();

        foreach ([null, now()->subDay()] as $trialEndsAt) {
            $practice = Practice::factory()->create(['trial_ends_at' => $trialEndsAt]);

            $builder = $page->applyTrial(
                $practice->newSubscription('default', $plan->stripe_price_id),
                $practice,
            );

            $this->assertNull($this->trialExpiresFor($builder));
        }
    }

    public function test_existing_subscriber_still_uses_billing_portal_path(): void
    {
        $practice = Practice::factory()->create([
            'trial_ends_at' => now()->addDays(20),
        ]);
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $user->assignRole(User::ROLE_OWNER);
        $plan = $this->plan();
        $practice->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_existing',
            'stripe_status' => 'active',
            'stripe_price' => $plan->stripe_price_id,
            'quantity' => 1,
        ]);

        $this->actingAs($user);

        $page = new class extends BillingPage {
            public bool $portalUsed = false;

            protected function redirectToBillingPortal(Practice $practice): mixed
            {
                $this->portalUsed = true;

                return 'portal';
            }
        };

        $this->assertSame('portal', $page->subscribeToPlan('solo'));
        $this->assertTrue($page->portalUsed);
    }

    public function test_billing_page_copy_explains_trial_billing_timing(): void
    {
        $practice = Practice::factory()->create([
            'trial_ends_at' => now()->addDays(12),
        ]);
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $user->assignRole(User::ROLE_OWNER);
        $this->plan();

        $this->actingAs($user);

        Livewire::test(BillingPage::class)
            ->assertSee('Your free trial ends on')
            ->assertSee('If you subscribe now, billing starts after your trial ends on');
    }

    public function test_billing_page_loads_with_stripe_customer_without_console_output(): void
    {
        $practice = Practice::factory()->create([
            'stripe_id' => 'cus_fake_existing',
            'trial_ends_at' => now()->addDays(12),
        ]);
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $user->assignRole(User::ROLE_OWNER);
        $this->plan();

        $this->app->instance(StripeSubscriptionSyncService::class, new class extends StripeSubscriptionSyncService {
            public function syncPractice(Practice $practice): array
            {
                return ['created' => 0, 'updated' => 0, 'skipped' => 0];
            }
        });

        $this->actingAs($user);

        $this->get('/admin/billing')
            ->assertOk()
            ->assertSee('Billing &amp; Subscription', false);

        Livewire::test(BillingPage::class)
            ->assertSee('Billing & Subscription')
            ->assertSee('Choose Your Plan');
    }

    public function test_starter_billing_page_hides_starter_stripe_action_and_shows_paid_upgrade_paths(): void
    {
        $practice = Practice::factory()->create([
            'plan_tier' => Practice::PLAN_TIER_STARTER,
            'trial_ends_at' => null,
        ]);
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $user->assignRole(User::ROLE_OWNER);

        $this->seedPlansForBillingPage([
            ['key' => 'solo', 'name' => 'Starter', 'price' => 0, 'stripe' => null, 'max' => 1],
            ['key' => 'clinic', 'name' => 'Plus', 'price' => 9900, 'stripe' => 'price_plus_test', 'max' => 5],
            ['key' => 'enterprise', 'name' => 'Clinic', 'price' => 19900, 'stripe' => 'price_clinic_test', 'max' => -1],
        ]);

        $this->actingAs($user);

        $this->get('/admin/billing')
            ->assertOk()
            ->assertSee('Current tier:')
            ->assertSee('Starter')
            ->assertSee('Starter is your free basic tier', false)
            ->assertDontSee("subscribeToPlan('solo')", false)
            ->assertSee("subscribeToPlan('clinic')", false)
            ->assertSee("subscribeToPlan('enterprise')", false);
    }

    public function test_plus_upgrade_action_uses_existing_paid_subscription_flow(): void
    {
        $practice = Practice::factory()->create(['plan_tier' => Practice::PLAN_TIER_STARTER]);
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $user->assignRole(User::ROLE_OWNER);

        SubscriptionPlan::create([
            'key' => 'clinic',
            'name' => 'Plus',
            'price_monthly' => 9900,
            'stripe_price_id' => 'price_plus_test',
            'max_practitioners' => 5,
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

        $this->assertSame('checkout-attempted', $page->subscribeToPlan('clinic'));
        $this->assertTrue($page->attempted);
    }

    public function test_current_tier_label_displays_plus_for_paid_clinic_subscription(): void
    {
        $practice = Practice::factory()->create([
            'plan_tier' => Practice::PLAN_TIER_STARTER,
        ]);
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $user->assignRole(User::ROLE_OWNER);

        SubscriptionPlan::create([
            'key' => 'clinic',
            'name' => 'Plus',
            'price_monthly' => 9900,
            'stripe_price_id' => 'price_plus_test',
            'max_practitioners' => 5,
            'features' => [],
            'is_active' => true,
        ]);

        $practice->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_plus_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_plus_test',
            'quantity' => 1,
        ]);

        $this->actingAs($user);

        Livewire::test(BillingPage::class)
            ->assertSee('Current tier:')
            ->assertSee('Plus');
    }

    private function plan(): SubscriptionPlan
    {
        return SubscriptionPlan::firstOrCreate(
            ['key' => 'solo'],
            [
                'name' => 'Solo Plan',
                'price_monthly' => 4900,
                'stripe_price_id' => 'price_solo_test',
                'max_practitioners' => 1,
                'features' => [],
                'is_active' => true,
            ],
        );
    }

    private function seedPlansForBillingPage(array $plans): void
    {
        foreach ($plans as $plan) {
            SubscriptionPlan::create([
                'key' => $plan['key'],
                'name' => $plan['name'],
                'price_monthly' => $plan['price'],
                'stripe_price_id' => $plan['stripe'],
                'max_practitioners' => $plan['max'],
                'features' => [],
                'is_active' => true,
            ]);
        }
    }

    private function trialExpiresFor(SubscriptionBuilder $builder): ?\Carbon\CarbonInterface
    {
        $property = new ReflectionProperty(SubscriptionBuilder::class, 'trialExpires');
        $property->setAccessible(true);

        return $property->getValue($builder);
    }
}

class BillingPageTrialHarness extends BillingPage
{
    public function applyTrial(SubscriptionBuilder $builder, Practice $practice): SubscriptionBuilder
    {
        return $this->applyAppTrialToSubscriptionBuilder($builder, $practice);
    }
}
