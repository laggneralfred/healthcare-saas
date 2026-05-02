<?php

namespace Tests\Feature;

use App\Filament\Resources\Patients\Pages\ViewPatient;
use App\Mail\PatientPortalMagicLinkMail;
use App\Models\Encounter;
use App\Models\MessageLog;
use App\Models\Patient;
use App\Models\PatientPortalToken;
use App\Models\Practice;
use App\Models\User;
use App\Services\PatientPortalTokenService;
use App\Support\PracticeAccessRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class PatientPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PracticeAccessRoles::ensureRoles();
    }

    public function test_patient_portal_token_creation_stores_only_hash(): void
    {
        [$practice, $admin, $patient] = $this->practiceAdminAndPatient();

        [$token, $plainToken] = app(PatientPortalTokenService::class)->createForExistingPatient($patient, $admin);

        $this->assertSame($practice->id, $token->practice_id);
        $this->assertSame($patient->id, $token->patient_id);
        $this->assertSame(PatientPortalToken::PURPOSE_EXISTING_PATIENT_PORTAL, $token->purpose);
        $this->assertSame($admin->id, $token->created_by_user_id);
        $this->assertNotSame($plainToken, $token->token_hash);
        $this->assertSame(hash('sha256', $plainToken), $token->token_hash);
        $this->assertFalse(PatientPortalToken::withoutPracticeScope()->where('token_hash', $plainToken)->exists());
    }

    public function test_patient_portal_token_verification_marks_usage(): void
    {
        [, $admin, $patient] = $this->practiceAdminAndPatient();
        [$token, $plainToken] = app(PatientPortalTokenService::class)->createForExistingPatient($patient, $admin);

        $verified = app(PatientPortalTokenService::class)->verifyExistingPatientToken($plainToken);

        $this->assertNotNull($verified);
        $this->assertSame($token->id, $verified->id);
        $this->assertNotNull($verified->used_at);
        $this->assertNotNull($verified->last_used_at);
    }

    public function test_patient_portal_rejects_expired_wrong_and_cross_practice_tokens(): void
    {
        [$practice, $admin, $patient] = $this->practiceAdminAndPatient();
        $otherPractice = Practice::factory()->create();
        $otherPatient = Patient::factory()->create(['practice_id' => $otherPractice->id]);

        [, $expiredPlainToken] = app(PatientPortalTokenService::class)
            ->createForExistingPatient($patient, $admin, now()->subMinute());

        $this->assertNull(app(PatientPortalTokenService::class)->verifyExistingPatientToken($expiredPlainToken));
        $this->assertNull(app(PatientPortalTokenService::class)->verifyExistingPatientToken('not-a-real-token'));

        $plainToken = 'cross-practice-token';
        PatientPortalToken::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'patient_id' => $otherPatient->id,
            'purpose' => PatientPortalToken::PURPOSE_EXISTING_PATIENT_PORTAL,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDay(),
            'created_by_user_id' => $admin->id,
        ]);

        $this->assertNull(app(PatientPortalTokenService::class)->verifyExistingPatientToken($plainToken));
    }

    public function test_patient_magic_link_grants_dashboard_access_without_showing_clinical_notes(): void
    {
        [$practice, $admin, $patient] = $this->practiceAdminAndPatient();
        Encounter::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'visit_notes' => 'Private clinical note should not be visible.',
        ]);
        [, $plainToken] = app(PatientPortalTokenService::class)->createForExistingPatient($patient, $admin);

        $this->get(route('patient.dashboard'))
            ->assertRedirect(route('patient.portal.invalid'));

        $this->get(route('patient.magic-link', ['token' => $plainToken]))
            ->assertRedirect(route('patient.dashboard'));

        $this->get(route('patient.dashboard'))
            ->assertOk()
            ->assertSee('Welcome, '.$patient->first_name)
            ->assertSee($practice->name)
            ->assertSee('Log out')
            ->assertDontSee('Private clinical note should not be visible');

        $this->post(route('patient.logout'))
            ->assertRedirect(route('patient.portal.logged-out'));

        $this->get(route('patient.dashboard'))
            ->assertRedirect(route('patient.portal.invalid'));
    }

    public function test_patient_magic_link_rejects_expired_and_wrong_tokens(): void
    {
        [, $admin, $patient] = $this->practiceAdminAndPatient();
        [, $expiredPlainToken] = app(PatientPortalTokenService::class)
            ->createForExistingPatient($patient, $admin, now()->subMinute());

        $this->get(route('patient.magic-link', ['token' => $expiredPlainToken]))
            ->assertRedirect(route('patient.portal.invalid'));

        $this->get(route('patient.magic-link', ['token' => 'wrong-token']))
            ->assertRedirect(route('patient.portal.invalid'));
    }

    public function test_staff_can_send_patient_portal_magic_link_email_from_patient_record(): void
    {
        Mail::fake();

        [$practice, $admin, $patient] = $this->practiceAdminAndPatient();

        $this->actingAs($admin);

        Livewire::test(ViewPatient::class, ['record' => $patient->id])
            ->callAction('send_portal_link')
            ->assertHasNoActionErrors();

        $token = PatientPortalToken::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('patient_id', $patient->id)
            ->firstOrFail();

        $this->assertSame(PatientPortalToken::PURPOSE_EXISTING_PATIENT_PORTAL, $token->purpose);

        Mail::assertSent(PatientPortalMagicLinkMail::class, function (PatientPortalMagicLinkMail $mail) use ($patient): bool {
            return $mail->hasTo($patient->email)
                && str_contains($mail->portalUrl, '/patient/magic-link/');
        });

        $this->assertSame('sent', MessageLog::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('patient_id', $patient->id)
            ->where('channel', 'email')
            ->firstOrFail()
            ->status);
    }

    private function practiceAdminAndPatient(): array
    {
        $practice = Practice::factory()->create(['name' => 'Portal Test Clinic']);
        $admin = User::factory()->create(['practice_id' => $practice->id]);
        $admin->assignRole(User::ROLE_ADMINISTRATOR);
        $patient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => 'Nora',
            'last_name' => 'Portal',
            'preferred_name' => null,
            'email' => 'nora@example.test',
        ]);

        return [$practice, $admin, $patient];
    }
}
