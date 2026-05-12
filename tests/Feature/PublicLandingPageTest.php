<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_presents_professional_practiq_positioning_and_links(): void
    {
        $this->get('http://localhost/')
            ->assertSuccessful()
            ->assertDontSee('noindex', false)
            ->assertSee('Simple practice software for busy healthcare providers.')
            ->assertSee('Practiq helps small practices manage visit notes, intake forms, appointment requests, follow-up, checkout tracking, and simple reports — without adding more admin work to your day.')
            ->assertSee('application/ld+json', false)
            ->assertSee('Organization', false)
            ->assertSee('SoftwareApplication', false)
            ->assertSee('Practiq', false)
            ->assertSee('https://practiqapp.com/', false)
            ->assertSee('How Practiq Helps')
            ->assertSee('Built for the reality of a busy small practice')
            ->assertSee('Keep notes, forms, follow-up, and checkout in one practical workflow')
            ->assertSee('See the daily workflow in two minutes')
            ->assertSee('Watch a quick overview of how Practiq supports setup, appointment requests, documentation, follow-up, and financial exports for small practices.')
            ->assertSee('Built for small healthcare practices')
            ->assertSee('/practice-software-for-acupuncturists', false)
            ->assertSee('/massage-therapy-practice-software', false)
            ->assertSee('/chiropractic-practice-software', false)
            ->assertSee('/physiotherapy-practice-software', false)
            ->assertSee('/wellness-practice-software', false)
            ->assertSee('Practice statistics and financial exports')
            ->assertSee('What Practiq Is Not')
            ->assertSee('Focused on daily workflow, not everything in healthcare.')
            ->assertSee('Start with editable defaults, not an empty system.')
            ->assertSee('Stripe is used for Practiq subscription billing.')
            ->assertSee('Growing Practice')
            ->assertSee('/videos/practiq-product-demo.mp4', false)
            ->assertSee('Watch Overview')
            ->assertSee('/register', false)
            ->assertSee('https://demo.practiqapp.com/demo-login', false)
            ->assertSee('/user-instructions', false)
            ->assertSee('/admin/login', false);
    }

    public function test_apex_host_shows_public_landing_page(): void
    {
        $this->get('https://practiqapp.com/')
            ->assertSuccessful()
            ->assertSee('Simple practice software for busy healthcare providers.')
            ->assertSee('How Practiq Helps');
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

    public function test_public_sitemap_contains_only_indexable_core_pages(): void
    {
        $response = $this->get('/sitemap.xml');

        $response
            ->assertSuccessful()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('https://practiqapp.com/', false)
            ->assertSee('https://practiqapp.com/register', false)
            ->assertSee('https://practiqapp.com/legal/privacy', false)
            ->assertSee('https://practiqapp.com/practice-software-for-acupuncturists', false)
            ->assertSee('https://practiqapp.com/massage-therapy-practice-software', false)
            ->assertSee('https://practiqapp.com/chiropractic-practice-software', false)
            ->assertSee('https://practiqapp.com/physiotherapy-practice-software', false)
            ->assertSee('https://practiqapp.com/wellness-practice-software', false)
            ->assertDontSee('/admin', false)
            ->assertDontSee('/onboarding', false);
    }

    public function test_robots_txt_includes_sitemap_directive(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertIsString($robots);
        $this->assertStringContainsString('Sitemap: https://practiqapp.com/sitemap.xml', $robots);
        $this->assertStringContainsString('Allow: /', $robots);
    }

    public function test_practitioner_seo_landing_pages_load_with_unique_positioning(): void
    {
        $pages = [
            '/practice-software-for-acupuncturists' => [
                'Practice software for busy acupuncturists.',
                'acupuncture practice software',
            ],
            '/massage-therapy-practice-software' => [
                'Practice software for busy massage therapists.',
                'massage therapy practice software',
            ],
            '/chiropractic-practice-software' => [
                'Practice software for busy chiropractors.',
                'chiropractic practice software',
            ],
            '/physiotherapy-practice-software' => [
                'Practice software for busy physiotherapists.',
                'physiotherapy practice software',
            ],
            '/wellness-practice-software' => [
                'Practice software for busy wellness practitioners.',
                'wellness practice software',
            ],
        ];

        foreach ($pages as $url => [$h1, $seoPhrase]) {
            $this->get($url)
                ->assertSuccessful()
                ->assertDontSee('noindex', false)
                ->assertSee($h1)
                ->assertSee($seoPhrase)
                ->assertSee('What Practiq Helps With')
                ->assertSee('Try Practiq with starter settings already in place.')
                ->assertSee('/register', false)
                ->assertSee('/#overview-video', false);
        }
    }
}
