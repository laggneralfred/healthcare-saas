<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceGracePeriodReadOnly;
use App\Http\Middleware\RequiresActiveSubscription;
use App\Models\Practice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RequiresActiveSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Route::has('test.subscription.protected')) {
            Route::middleware(RequiresActiveSubscription::class)
                ->get('/_test/subscription/protected', fn () => response('ok', 200))
                ->name('test.subscription.protected');
        }

        if (! Route::has('test.grace.create')) {
            Route::middleware(EnforceGracePeriodReadOnly::class)
                ->get('/_test/grace/create', fn () => response('ok', 200))
                ->name('test.grace.create');
        }
    }

    public function test_starter_practice_without_subscription_can_access_protected_route(): void
    {
        $practice = Practice::factory()->create([
            'plan_tier' => Practice::PLAN_TIER_STARTER,
            'trial_ends_at' => null,
        ]);
        $user = User::factory()->create(['practice_id' => $practice->id]);

        $this->actingAs($user)
            ->get('/_test/subscription/protected')
            ->assertOk();
    }

    public function test_expired_trial_starter_practice_can_still_access_protected_route(): void
    {
        $practice = Practice::factory()->create([
            'plan_tier' => Practice::PLAN_TIER_STARTER,
            'trial_ends_at' => now()->subDays(40),
        ]);
        $user = User::factory()->create(['practice_id' => $practice->id]);

        $this->actingAs($user)
            ->get('/_test/subscription/protected')
            ->assertOk();
    }

    public function test_plus_and_clinic_practices_without_trial_or_subscription_are_redirected_to_subscribe(): void
    {
        $plus = Practice::factory()->create([
            'plan_tier' => Practice::PLAN_TIER_PLUS,
            'trial_ends_at' => null,
        ]);
        $clinic = Practice::factory()->create([
            'plan_tier' => Practice::PLAN_TIER_CLINIC,
            'trial_ends_at' => null,
        ]);

        $plusOwner = User::factory()->create(['practice_id' => $plus->id]);
        $clinicOwner = User::factory()->create(['practice_id' => $clinic->id]);

        $this->actingAs($plusOwner)
            ->get('/_test/subscription/protected')
            ->assertRedirect('/subscribe');

        $this->actingAs($clinicOwner)
            ->get('/_test/subscription/protected')
            ->assertRedirect('/subscribe');
    }

    public function test_plus_or_clinic_practice_with_active_subscription_still_has_access(): void
    {
        $practice = Practice::factory()->create([
            'plan_tier' => Practice::PLAN_TIER_PLUS,
            'trial_ends_at' => null,
        ]);
        $user = User::factory()->create(['practice_id' => $practice->id]);

        DB::table('subscriptions')->insert([
            'practice_id' => $practice->id,
            'type' => 'default',
            'stripe_id' => 'sub_active_'.$practice->id,
            'stripe_status' => 'active',
            'stripe_price' => 'price_plus_test',
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/_test/subscription/protected')
            ->assertOk();
    }

    public function test_plus_or_clinic_practice_with_trial_grace_still_hits_read_only_block_for_write_pages(): void
    {
        $practice = Practice::factory()->create([
            'plan_tier' => Practice::PLAN_TIER_PLUS,
            'trial_ends_at' => now()->subDay(),
        ]);
        $user = User::factory()->create(['practice_id' => $practice->id]);

        $response = $this->actingAs($user)->get('/_test/subscription/protected');

        $response->assertOk();
        $this->assertTrue(session()->has('trial_grace'));

        $this->actingAs($user)
            ->withSession(['trial_grace' => true])
            ->get('/_test/grace/create')
            ->assertRedirect('/admin/dashboard');
    }
}
