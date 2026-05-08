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
            ->assertSee('If you subscribe now, billing starts after your trial ends on')
            ->assertSee('Billing starts after your trial ends on');
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
