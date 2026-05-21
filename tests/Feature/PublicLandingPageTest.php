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
            ->assertSee('Your day is for patients.')
            ->assertSee('Practiq keeps the everyday work of a small clinic organized')
            ->assertSee('application/ld+json', false)
            ->assertSee('Organization', false)
            ->assertSee('SoftwareApplication', false)
            ->assertSee('Practiq', false)
            ->assertSee('https://practiqapp.com/', false)
            ->assertSee('How Practiq helps')
            ->assertSee('One practical workflow for the whole day')
            ->assertSee('Keep the core pieces of a small practice connected')
            ->assertSee('Built for small healthcare practices')
            ->assertSee('/practice-software-for-acupuncturists', false)
            ->assertSee('/massage-therapy-practice-software', false)
            ->assertSee('/chiropractic-practice-software', false)
            ->assertSee('/physiotherapy-practice-software', false)
            ->assertSee('/wellness-practice-software', false)
            ->assertSee('What Practiq is — and what it is not')
            ->assertSee('Stripe handles Practiq subscription billing.')
            ->assertSee('$0')
            ->assertSee('Adds optional AI drafting and advanced follow-up tools.')
            ->assertSee('Clinic')
            ->assertSee('/register', false)
            ->assertSee('https://demo.practiqapp.com/demo-login', false)
            ->assertSee('/blog', false)
            ->assertSee('/blog/small-clinic-visit-notes', false)
            ->assertSee('/blog/acupuncture-visit-note-examples', false)
            ->assertSee('/user-instructions', false)
            ->assertSee('/admin/login', false);
    }

    public function test_apex_host_shows_public_landing_page(): void
    {
        $this->get('https://practiqapp.com/')
            ->assertSuccessful()
            ->assertSee('Your day is for patients.')
            ->assertSee('How Practiq helps');
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
            ->assertSee('https://practiqapp.com/blog', false)
            ->assertSee('https://practiqapp.com/blog/small-clinic-visit-notes', false)
            ->assertSee('https://practiqapp.com/blog/acupuncture-visit-note-examples', false)
            ->assertSee('https://practiqapp.com/blog/soap-notes-vs-simple-visit-notes', false)
            ->assertSee('https://practiqapp.com/blog/what-to-include-in-a-visit-note', false)
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
                ->assertSee('How Practiq helps')
                ->assertSee('Getting started')
                ->assertSee('Starter is free. Upgrade to Plus or Clinic when you need more.')
                ->assertSee('/register', false)
                ->assertSee('/#practice-types', false);
        }
    }

    public function test_blog_article_is_public_and_indexable(): void
    {
        $this->get('/blog/small-clinic-visit-notes')
            ->assertSuccessful()
            ->assertDontSee('noindex', false)
            ->assertSee('Keeping Up With Visit Notes')
            ->assertSee('/blog', false)
            ->assertSee('/blog/what-to-include-in-a-visit-note', false)
            ->assertSee('/blog/soap-notes-vs-simple-visit-notes', false)
            ->assertSee('/register', false);
    }

    public function test_acupuncture_blog_article_is_public_and_indexable(): void
    {
        $this->get('/blog/acupuncture-visit-note-examples')
            ->assertSuccessful()
            ->assertDontSee('noindex', false)
            ->assertSee('Acupuncture Notes That Stay Useful')
            ->assertSee('/practice-software-for-acupuncturists', false)
            ->assertSee('/blog/small-clinic-visit-notes', false)
            ->assertSee('/register', false);
    }

    public function test_blog_index_is_public_and_lists_articles(): void
    {
        $this->get('/blog')
            ->assertSuccessful()
            ->assertDontSee('noindex', false)
            ->assertSee('/blog/what-to-include-in-a-visit-note', false)
            ->assertSee('/blog/small-clinic-visit-notes', false)
            ->assertSee('/blog/acupuncture-visit-note-examples', false)
            ->assertSee('/blog/soap-notes-vs-simple-visit-notes', false);
    }

    public function test_visit_note_basics_blog_article_is_public_and_indexable(): void
    {
        $this->get('/blog/what-to-include-in-a-visit-note')
            ->assertSuccessful()
            ->assertDontSee('noindex', false)
            ->assertSee('What Belongs in a Visit Note?')
            ->assertSee('/blog/small-clinic-visit-notes', false)
            ->assertSee('/blog/soap-notes-vs-simple-visit-notes', false)
            ->assertSee('/register', false);
    }

    public function test_soap_vs_simple_blog_article_is_public_and_indexable(): void
    {
        $this->get('/blog/soap-notes-vs-simple-visit-notes')
            ->assertSuccessful()
            ->assertDontSee('noindex', false)
            ->assertSee('SOAP Notes or Simple Notes?')
            ->assertSee('/blog/small-clinic-visit-notes', false)
            ->assertSee('/blog/what-to-include-in-a-visit-note', false)
            ->assertSee('/register', false);
    }
}
