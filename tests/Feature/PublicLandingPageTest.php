<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_presents_practiq_follow_up_positioning_and_links(): void
    {
        $this->get('http://localhost/')
            ->assertSuccessful()
            ->assertSee('Keep your practice organized')
            ->assertSee('patients from slipping through the cracks')
            ->assertSee('/register', false)
            ->assertSee('https://demo.practiqapp.com/demo-login', false)
            ->assertSee('/user-instructions', false)
            ->assertSee('/admin/login', false);
    }

    public function test_apex_host_shows_public_landing_page(): void
    {
        $this->get('https://practiqapp.com/')
            ->assertSuccessful()
            ->assertSee('Keep your practice organized')
            ->assertSee('patients from slipping through the cracks');
    }

    public function test_app_host_root_redirects_guests_to_login(): void
    {
        $this->get('https://app.practiqapp.com/')
            ->assertRedirect('/login');
    }

    public function test_app_host_root_redirects_authenticated_users_to_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('https://app.practiqapp.com/')
            ->assertRedirect('/admin/dashboard');
    }

    public function test_app_host_login_alias_uses_existing_backend_login(): void
    {
        $this->get('https://app.practiqapp.com/login')
            ->assertRedirect('/admin/login');
    }

    public function test_user_instructions_page_loads_with_safe_workflow_reminders(): void
    {
        $this->get('/user-instructions')
            ->assertSuccessful()
            ->assertSee('Getting Started with Practiq')
            ->assertSee('Saving a draft does not contact the patient')
            ->assertSee('AI suggestions are drafts only');
    }
}
