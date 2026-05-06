<?php

namespace Tests\Feature;

use App\Filament\Resources\Practices\Pages\EditPractice;
use App\Mail\PatientPortalMagicLinkMail;
use App\Models\NewPatientInterest;
use App\Models\Patient;
use App\Models\PatientPortalToken;
use App\Models\Practice;
use App\Models\User;
use App\Support\PracticeAccessRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class PublicPracticeLinksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PracticeAccessRoles::ensureRoles();
        Cache::clear();
    }

    public function test_slug_resolves_correct_practice(): void
    {
        Practice::factory()->create([
            'name' => 'Other Clinic',
            'slug' => 'other-clinic',
        ]);
        $practice = Practice::factory()->create([
            'name' => 'Website Clinic',
            'slug' => 'website-clinic',
        ]);

        $this->get(route('public.practice.new-patient', ['practiceSlug' => $practice->slug]))
            ->assertOk()
            ->assertSee('Website Clinic')
            ->assertDontSee('Other Clinic');
    }

    public function test_new_patient_form_creates_interest_for_slug_practice(): void
    {
        $practice = Practice::factory()->create(['slug' => 'slug-practice']);
        $otherPractice = Practice::factory()->create(['slug' => 'other-practice']);

        $this->post(route('public.practice.new-patient.store', ['practiceSlug' => $practice->slug]), [
            'first_name' => 'Public',
            'last_name' => 'Patient',
            'email' => 'public@example.test',
            'phone' => '555-555-1000',
            'preferred_service' => 'Acupuncture',
            'preferred_days_times' => 'Tuesday morning',
            'message' => 'I would like to become a patient.',
            'contact_acknowledgement' => '1',
        ])->assertRedirect(route('new-patient.thanks'));

        $this->assertDatabaseHas('new_patient_interests', [
            'practice_id' => $practice->id,
            'email' => 'public@example.test',
            'status' => NewPatientInterest::STATUS_NEW,
        ]);
        $this->assertDatabaseMissing('new_patient_interests', [
            'practice_id' => $otherPractice->id,
            'email' => 'public@example.test',
        ]);
    }

    public function test_existing_patient_access_sends_magic_link_for_matching_patient(): void
    {
        Mail::fake();

        $practice = Practice::factory()->create(['slug' => 'portal-clinic']);
        $patient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'email' => 'nora@example.test',
            'first_name' => 'Nora',
        ]);

        $this->post(route('public.practice.existing-patient.store', ['practiceSlug' => $practice->slug]), [
            'email' => 'NORA@example.test',
        ])->assertRedirect();

        $token = PatientPortalToken::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('patient_id', $patient->id)
            ->firstOrFail();

        $this->assertSame(PatientPortalToken::PURPOSE_EXISTING_PATIENT_PORTAL, $token->purpose);

        Mail::assertSent(PatientPortalMagicLinkMail::class, function (PatientPortalMagicLinkMail $mail) use ($patient): bool {
            return $mail->hasTo($patient->email)
                && str_contains($mail->portalUrl, '/patient/magic-link/');
        });
    }

    public function test_existing_patient_access_response_is_same_for_missing_email(): void
    {
        Mail::fake();

        $practice = Practice::factory()->create(['slug' => 'quiet-clinic']);
        Patient::factory()->create([
            'practice_id' => $practice->id,
            'email' => 'found@example.test',
        ]);

        $found = $this->from(route('public.practice.existing-patient', ['practiceSlug' => $practice->slug]))
            ->post(route('public.practice.existing-patient.store', ['practiceSlug' => $practice->slug]), [
                'email' => 'found@example.test',
            ]);
        $missing = $this->from(route('public.practice.existing-patient', ['practiceSlug' => $practice->slug]))
            ->post(route('public.practice.existing-patient.store', ['practiceSlug' => $practice->slug]), [
                'email' => 'missing@example.test',
            ]);

        $this->assertSame($found->getStatusCode(), $missing->getStatusCode());
        $this->assertSame($found->headers->get('Location'), $missing->headers->get('Location'));
        $this->assertSame(
            $found->baseResponse->getSession()->get('status'),
            $missing->baseResponse->getSession()->get('status'),
        );

        Mail::assertSent(PatientPortalMagicLinkMail::class, 1);
    }

    public function test_cross_practice_email_does_not_send_wrong_practice_link(): void
    {
        Mail::fake();

        $practice = Practice::factory()->create(['slug' => 'target-clinic']);
        $otherPractice = Practice::factory()->create(['slug' => 'other-clinic']);
        Patient::factory()->create([
            'practice_id' => $otherPractice->id,
            'email' => 'shared@example.test',
        ]);

        $this->post(route('public.practice.existing-patient.store', ['practiceSlug' => $practice->slug]), [
            'email' => 'shared@example.test',
        ])->assertRedirect();

        Mail::assertNothingSent();
        $this->assertSame(0, PatientPortalToken::withoutPracticeScope()->where('practice_id', $practice->id)->count());
    }

    public function test_existing_patient_access_is_rate_limited(): void
    {
        Mail::fake();

        $practice = Practice::factory()->create(['slug' => 'rate-limited-clinic']);

        for ($i = 0; $i < 6; $i++) {
            $this->post(route('public.practice.existing-patient.store', ['practiceSlug' => $practice->slug]), [
                'email' => "missing-{$i}@example.test",
            ])->assertRedirect();
        }

        $this->post(route('public.practice.existing-patient.store', ['practiceSlug' => $practice->slug]), [
            'email' => 'blocked@example.test',
        ])->assertTooManyRequests();
    }

    public function test_public_links_appear_in_practice_settings(): void
    {
        $practice = Practice::factory()->create([
            'name' => 'Settings Clinic',
            'slug' => 'settings-clinic',
        ]);
        $admin = User::factory()->create(['practice_id' => $practice->id]);
        $admin->assignRole(User::ROLE_ADMINISTRATOR);

        $this->actingAs($admin);

        Livewire::test(EditPractice::class, ['record' => $practice->id])
            ->assertSee('Website links')
            ->assertSee(route('public.practice.new-patient', ['practiceSlug' => $practice->slug]), false)
            ->assertSee(route('public.practice.existing-patient', ['practiceSlug' => $practice->slug]), false)
            ->assertSee(route('public.practice.request-appointment', ['practiceSlug' => $practice->slug]), false)
            ->assertSee('&lt;a href=&quot;'.route('public.practice.new-patient', ['practiceSlug' => $practice->slug]).'&quot;&gt;Request a New Patient Appointment&lt;/a&gt;', false)
            ->assertSee('&lt;a href=&quot;'.route('public.practice.existing-patient', ['practiceSlug' => $practice->slug]).'&quot;&gt;Existing Patient Access&lt;/a&gt;', false);
    }

    public function test_public_request_appointment_redirects_to_existing_patient_access(): void
    {
        $practice = Practice::factory()->create(['slug' => 'appointment-request-clinic']);

        $this->get(route('public.practice.request-appointment', ['practiceSlug' => $practice->slug]))
            ->assertRedirect(route('public.practice.existing-patient', ['practiceSlug' => $practice->slug]));
    }
}
