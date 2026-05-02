<?php

namespace Tests\Feature;

use App\Filament\Resources\NewPatientInterests\NewPatientInterestResource;
use App\Filament\Resources\NewPatientInterests\Pages\ViewNewPatientInterest;
use App\Mail\NewPatientIntakeFormMail;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\NewPatientInterest;
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

class NewPatientFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PracticeAccessRoles::ensureRoles();
    }

    public function test_staff_can_send_intake_forms_to_new_patient_interest(): void
    {
        Mail::fake();
        [$practice, $admin, $interest] = $this->practiceAdminAndInterest();

        $this->actingAs($admin);

        Livewire::test(ViewNewPatientInterest::class, ['record' => $interest->id])
            ->callAction('send_intake_forms')
            ->assertHasNoActionErrors();

        $template = FormTemplate::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('name', FormTemplate::DEFAULT_NEW_PATIENT_INTAKE_NAME)
            ->firstOrFail();
        $submission = FormSubmission::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('new_patient_interest_id', $interest->id)
            ->firstOrFail();
        $portalToken = PatientPortalToken::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('new_patient_interest_id', $interest->id)
            ->firstOrFail();

        $this->assertSame(FormSubmission::STATUS_PENDING, $submission->status);
        $this->assertSame($template->id, $submission->form_template_id);
        $this->assertNull($submission->patient_id);
        $this->assertSame(PatientPortalToken::PURPOSE_NEW_PATIENT_FORM, $portalToken->purpose);
        $this->assertNull($portalToken->patient_id);
        $this->assertNotNull($portalToken->token_hash);
        $this->assertSame(NewPatientInterest::STATUS_FORMS_SENT, $interest->refresh()->status);
        $this->assertSame($admin->id, $interest->responded_by_user_id);

        Mail::assertSent(NewPatientIntakeFormMail::class, function (NewPatientIntakeFormMail $mail) use ($interest): bool {
            return $mail->hasTo($interest->email)
                && str_contains($mail->formUrl, '/patient/new-patient-form/');
        });
    }

    public function test_new_patient_form_token_stores_only_hash_and_renders_form(): void
    {
        [, $admin, $interest] = $this->practiceAdminAndInterest();
        $submission = $this->pendingSubmissionFor($interest);
        [, $plainToken] = app(PatientPortalTokenService::class)->createForNewPatientInterest($interest, $admin);

        $this->assertFalse(PatientPortalToken::withoutPracticeScope()->where('token_hash', $plainToken)->exists());

        $this->get(route('patient.new-patient-form.show', ['token' => $plainToken]))
            ->assertOk()
            ->assertSee($interest->practice->name)
            ->assertSee($submission->formTemplate->name)
            ->assertSee('Main reason for visit')
            ->assertSee('This does not create a patient record');
    }

    public function test_new_patient_form_rejects_expired_and_wrong_tokens(): void
    {
        [, $admin, $interest] = $this->practiceAdminAndInterest();
        $this->pendingSubmissionFor($interest);
        [, $expiredPlainToken] = app(PatientPortalTokenService::class)
            ->createForNewPatientInterest($interest, $admin, now()->subMinute());

        $this->get(route('patient.new-patient-form.show', ['token' => $expiredPlainToken]))
            ->assertRedirect(route('patient.portal.invalid'));

        $this->get(route('patient.new-patient-form.show', ['token' => 'wrong-token']))
            ->assertRedirect(route('patient.portal.invalid'));
    }

    public function test_completed_form_stores_data_and_does_not_create_patient(): void
    {
        [, $admin, $interest] = $this->practiceAdminAndInterest();
        $submission = $this->pendingSubmissionFor($interest);
        [, $plainToken] = app(PatientPortalTokenService::class)->createForNewPatientInterest($interest, $admin);

        $this->post(route('patient.new-patient-form.store', ['token' => $plainToken]), [
            'fields' => [
                'date_of_birth' => '1988-04-12',
                'main_concern' => 'Neck tension and stress.',
                'health_history' => 'No major red flags.',
                'current_medications' => 'None listed.',
                'consent_to_contact' => '1',
            ],
        ])->assertRedirect(route('patient.new-patient-form.thanks'));

        $submission->refresh();
        $this->assertSame(FormSubmission::STATUS_SUBMITTED, $submission->status);
        $this->assertSame('Neck tension and stress.', $submission->submitted_data_json['main_concern']);
        $this->assertTrue($submission->submitted_data_json['consent_to_contact']);
        $this->assertSame(NewPatientInterest::STATUS_REVIEWING, $interest->refresh()->status);
        $this->assertSame(0, Patient::withoutPracticeScope()->count());

        $this->get(route('patient.new-patient-form.thanks'))
            ->assertOk()
            ->assertSee('Your forms have been submitted.');
    }

    public function test_staff_can_see_submitted_form_data_and_mark_it_reviewed_or_archived(): void
    {
        [, $admin, $interest] = $this->practiceAdminAndInterest();
        $submission = $this->pendingSubmissionFor($interest);
        $submission->update([
            'status' => FormSubmission::STATUS_SUBMITTED,
            'submitted_data_json' => [
                'main_concern' => 'Low back stiffness.',
                'consent_to_contact' => true,
            ],
        ]);

        $this->actingAs($admin);

        $this->get(NewPatientInterestResource::getUrl('view', ['record' => $interest->id]))
            ->assertOk()
            ->assertSee('Form submissions')
            ->assertSee('Low back stiffness.')
            ->assertSee('Consent To Contact')
            ->assertSee('Yes');

        Livewire::test(ViewNewPatientInterest::class, ['record' => $interest->id])
            ->callAction('mark_form_reviewed')
            ->assertHasNoActionErrors();
        $this->assertSame(FormSubmission::STATUS_REVIEWED, $submission->refresh()->status);
        $this->assertSame($admin->id, $submission->reviewed_by_user_id);

        Livewire::test(ViewNewPatientInterest::class, ['record' => $interest->id])
            ->callAction('archive_form')
            ->assertHasNoActionErrors();
        $this->assertSame(FormSubmission::STATUS_ARCHIVED, $submission->refresh()->status);
    }

    public function test_cross_practice_staff_cannot_send_or_view_forms_for_another_practices_interest(): void
    {
        [, $admin] = $this->practiceWithAdmin();
        [$otherPractice] = $this->practiceWithAdmin();
        $otherInterest = $this->interestFor($otherPractice, [
            'first_name' => 'Other',
            'last_name' => 'Interest',
            'email' => 'other@example.test',
        ]);

        $this->actingAs($admin);

        $this->get(NewPatientInterestResource::getUrl('view', ['record' => $otherInterest->id]))
            ->assertNotFound();
    }

    private function pendingSubmissionFor(NewPatientInterest $interest): FormSubmission
    {
        $template = FormTemplate::findOrCreateDefaultNewPatientIntake($interest->practice_id);

        return FormSubmission::withoutPracticeScope()->create([
            'practice_id' => $interest->practice_id,
            'new_patient_interest_id' => $interest->id,
            'form_template_id' => $template->id,
            'status' => FormSubmission::STATUS_PENDING,
        ]);
    }

    private function practiceAdminAndInterest(): array
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        $interest = $this->interestFor($practice);

        return [$practice, $admin, $interest];
    }

    private function practiceWithAdmin(): array
    {
        $practice = Practice::factory()->create(['name' => 'Warm Clinic']);
        $admin = User::factory()->create(['practice_id' => $practice->id]);
        $admin->assignRole(User::ROLE_ADMINISTRATOR);

        return [$practice, $admin];
    }

    private function interestFor(Practice $practice, array $overrides = []): NewPatientInterest
    {
        return NewPatientInterest::withoutPracticeScope()->create(array_merge([
            'practice_id' => $practice->id,
            'first_name' => 'Nora',
            'last_name' => 'Interested',
            'email' => 'nora@example.test',
            'phone' => '(555) 010-1234',
            'preferred_service' => 'Acupuncture',
            'preferred_days_times' => 'Tuesday mornings',
            'message' => 'Looking for gentle care.',
            'status' => NewPatientInterest::STATUS_NEW,
        ], $overrides));
    }
}
