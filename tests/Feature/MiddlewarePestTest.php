<?php

use App\Models\Practice;
use App\Models\User;

// ─── Test 1: Guest is redirected away from /admin/dashboard ──────────────────

it('redirects guests away from /admin/dashboard', function () {
    $response = $this->get('/admin/dashboard');

    $response->assertRedirectContains('login');
});

// ─── Test 2: DemoModeMiddleware blocks GET /create for demo users ─────────────

it('blocks a demo user from accessing the create patient page', function () {
    $practice = Practice::factory()->create(['is_demo' => true]);
    $user = User::factory()->create(['practice_id' => $practice->id]);

    $response = $this->actingAs($user)->get('/admin/patients/create');

    // DemoModeMiddleware redirects /create pages for demo users
    $response->assertRedirect('/admin/dashboard');
});

// ─── Test 3: EnforceGracePeriodReadOnly blocks creates during grace period ────

it('blocks a grace-period user from accessing the create patient page', function () {
    $practice = Practice::factory()->create([
        'trial_ends_at' => now()->subDay(),
    ]);
    $user = User::factory()->create(['practice_id' => $practice->id]);

    $response = $this->actingAs($user)
        ->withSession(['trial_grace' => true])
        ->get('/admin/patients/create');

    // EnforceGracePeriodReadOnly redirects /create pages during grace period
    $response->assertRedirect('/admin/dashboard');
});
