<?php

namespace Tests\Feature;

use App\Filament\Resources\TrialSignups\TrialSignupResource;
use App\Mail\TrialSignupNotificationMail;
use App\Models\Practice;
use App\Models\TrialSignup;
use App\Models\User;
use App\Support\PracticeType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TrialSignupNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_registration_creates_trial_signup_record(): void
    {
        Mail::fake();

        $response = $this->withServerVariables([
            'HTTP_USER_AGENT' => 'Trial Signup Test Browser',
            'REMOTE_ADDR' => '203.0.113.20',
        ])->post('/register', [
            'practice_name' => 'Trial Visibility Clinic',
            'first_name' => 'Taylor',
            'last_name' => 'Owner',
            'email' => 'taylor-owner@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'practice_type' => PracticeType::MASSAGE_THERAPY,
            'phone' => '555-111-2222',
            'referral_source' => 'Colleague',
            'terms_accepted' => true,
        ]);

        $response->assertRedirect('/onboarding');

        $practice = Practice::where('name', 'Trial Visibility Clinic')->firstOrFail();
        $user = User::where('email', 'taylor-owner@example.com')->firstOrFail();
        $signup = TrialSignup::withoutPracticeScope()->firstOrFail();

        $this->assertSame($practice->id, $signup->practice_id);
        $this->assertSame($user->id, $signup->user_id);
        $this->assertSame('Taylor Owner', $signup->name);
        $this->assertSame('taylor-owner@example.com', $signup->email);
        $this->assertSame('555-111-2222', $signup->phone);
        $this->assertSame('Trial Visibility Clinic', $signup->practice_name);
        $this->assertSame('Massage Therapy', $signup->profession);
        $this->assertSame(PracticeType::MASSAGE_THERAPY, $signup->practice_type);
        $this->assertSame('Colleague', $signup->heard_about_us);
        $this->assertSame('register', $signup->source);
        $this->assertSame('Trial Signup Test Browser', $signup->user_agent);
        $this->assertNotNull($signup->signed_up_at);
    }

    public function test_failed_registration_does_not_create_trial_signup_or_send_notification(): void
    {
        Mail::fake();

        $response = $this->post('/register', [
            'practice_name' => 'Failed Trial Signup',
            'first_name' => 'Failed',
            'last_name' => 'Owner',
            'email' => 'failed-owner@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'practice_type' => PracticeType::GENERAL_WELLNESS,
            'terms_accepted' => false,
        ]);

        $response->assertSessionHasErrors('terms_accepted');
        $this->assertSame(0, TrialSignup::withoutPracticeScope()->count());
        Mail::assertNotSent(TrialSignupNotificationMail::class);
    }

    public function test_notification_email_is_sent_after_successful_registration(): void
    {
        Mail::fake();
        config(['mail.trial_signup_notification_email' => 'alfred@example.com']);

        $this->post('/register', [
            'practice_name' => 'Notification Clinic',
            'first_name' => 'Notify',
            'last_name' => 'Owner',
            'email' => 'notify-owner@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'practice_type' => PracticeType::TCM_ACUPUNCTURE,
            'phone' => '555-444-3333',
            'referral_source' => 'Google',
            'terms_accepted' => true,
        ]);

        Mail::assertSent(TrialSignupNotificationMail::class, function (TrialSignupNotificationMail $mail): bool {
            return $mail->hasTo('alfred@example.com')
                && $mail->trialSignup->email === 'notify-owner@example.com'
                && $mail->trialSignup->practice_name === 'Notification Clinic'
                && $mail->trialSignup->profession === 'TCM Acupuncture'
                && $mail->trialSignup->phone === '555-444-3333'
                && $mail->trialSignup->heard_about_us === 'Google';
        });
    }

    public function test_signedup_page_requires_authentication(): void
    {
        $this->get('/admin/signedup')
            ->assertRedirect('/admin/login');
    }

    public function test_super_admin_can_view_trial_signups(): void
    {
        $practice = Practice::factory()->create(['name' => 'Visible Signup Practice']);
        $user = User::factory()->create([
            'practice_id' => $practice->id,
            'name' => 'Visible Signup User',
        ]);

        TrialSignup::factory()->create([
            'practice_id' => $practice->id,
            'user_id' => $user->id,
            'name' => 'Visible Signup User',
            'email' => 'visible-signup@example.com',
            'practice_name' => 'Visible Signup Practice',
            'profession' => 'Physiotherapy',
        ]);

        $superAdmin = User::factory()->create(['practice_id' => null]);

        $this->actingAs($superAdmin)
            ->get('/admin/signedup')
            ->assertSuccessful()
            ->assertSee('Visible Signup User')
            ->assertSee('visible-signup@example.com')
            ->assertSee('Visible Signup Practice');

        $this->assertTrue(TrialSignupResource::canAccess());
    }

    public function test_normal_practice_user_cannot_access_global_trial_signups(): void
    {
        $practice = Practice::factory()->create();
        $user = User::factory()->create(['practice_id' => $practice->id]);

        $this->actingAs($user)
            ->get('/admin/signedup')
            ->assertForbidden();

        $this->assertFalse(TrialSignupResource::canAccess());
    }

    public function test_make_owner_admin_command_creates_global_admin_user(): void
    {
        $this->artisan('practiq:make-owner-admin', [
            'email' => 'owner-admin@example.com',
            '--name' => 'Owner Admin',
            '--password' => 'temporary-password',
        ])
            ->expectsOutput('Owner/global admin user created.')
            ->expectsOutput('Email: owner-admin@example.com')
            ->expectsOutput('practice_id: null')
            ->assertExitCode(0);

        $user = User::where('email', 'owner-admin@example.com')->firstOrFail();

        $this->assertSame('Owner Admin', $user->name);
        $this->assertNull($user->practice_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('temporary-password', $user->password));
        $this->assertTrue($user->isPracticeSuperAdmin());
    }

    public function test_make_owner_admin_command_promotes_existing_user_without_changing_password(): void
    {
        $practice = Practice::factory()->create(['name' => 'Previous Practice']);
        $user = User::factory()->create([
            'email' => 'promote-owner@example.com',
            'practice_id' => $practice->id,
            'password' => Hash::make('existing-password'),
            'email_verified_at' => null,
        ]);

        TrialSignup::factory()->create([
            'practice_id' => $practice->id,
            'name' => 'Promoted Viewer',
            'email' => 'promoted-viewer@example.com',
            'practice_name' => 'Previous Practice',
        ]);

        $this->artisan('practiq:make-owner-admin', [
            'email' => 'promote-owner@example.com',
            '--password' => 'ignored-new-password',
        ])
            ->expectsOutput('Existing user found. Password was not changed.')
            ->expectsOutput('Owner/global admin user promoted.')
            ->assertExitCode(0);

        $user->refresh();

        $this->assertNull($user->practice_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('existing-password', $user->password));
        $this->assertFalse(Hash::check('ignored-new-password', $user->password));

        $this->actingAs($user)
            ->get('/admin/signedup')
            ->assertSuccessful()
            ->assertSee('Promoted Viewer')
            ->assertSee('promoted-viewer@example.com');
    }
}
