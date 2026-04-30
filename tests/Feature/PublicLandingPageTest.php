<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicLandingPageTest extends TestCase
{
    public function test_homepage_presents_practiq_follow_up_positioning_and_links(): void
    {
        $this->get('/')
            ->assertSuccessful()
            ->assertSee('Keep your practice organized')
            ->assertSee('patients from slipping through the cracks')
            ->assertSee('/register', false)
            ->assertSee('https://demo.practiqapp.com/demo-login', false)
            ->assertSee('/user-instructions', false);
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
