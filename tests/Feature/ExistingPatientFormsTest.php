<?php

namespace Tests\Feature;

use App\Filament\Resources\Patients\Pages\ViewPatient;
use App\Mail\ExistingPatientFormMail;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
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

class ExistingPatientFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PracticeAccessRoles::ensureRoles();
    }

    public function test_staff_can_send_forms_to_existing_patient(): void
    {
        Mail::fake();
        [$practice, $admin, $patient] = $this->practiceAdminAndPatient();

        $this->actingAs($admin);

        Livewire::test(ViewPatient::class, ['record' => $patient->id])
            ->callAction('send_forms')
            ->assertHasNoActionErrors();

        $submission = FormSubmission::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('patient_id', $patient->id)
            ->firstOrFail();
        $portalToken = PatientPortalToken::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('patient_id', $patient->id)
            ->where('purpose', PatientPortalToken::PURPOSE_EXISTING_PATIENT_FORM)
            ->firstOrFail();

        $this->assertSame(FormSubmission::STATUS_PENDING, $submission->status);
        $this->assertNull($submission->new_patient_interest_id);
        $this->assertNotSame('', $portalToken->token_hash);
        $this->assertSame('sent', MessageLog::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('patient_id', $patient->id)
            ->where('channel', 'email')
            ->firstOrFail()
            ->status);

        Mail::assertSent(ExistingPatientFormMail::class, function (ExistingPatientFormMail $mail) use ($patient): bool {
            return $mail->hasTo($patient->email)
                && str_contains($mail->formUrl, '/patient/magic-link/');
        });
    }

    public function test_existing_patient_form_token_stores_only_hash_and_opens_forms(): void
    {
        [, $admin, $patient] = $this->practiceAdminAndPatient();
        $this->pendingSubmissionFor($patient);
        [, $plainToken] = app(PatientPortalTokenService::class)->createForExistingPatientForm($patient, $admin);

        $this->assertFalse(PatientPortalToken::withoutPracticeScope()->where('token_hash', $plainToken)->exists());

        $this->get(route('patient.magic-link', ['token' => $plainToken]))
            ->assertRedirect(route('patient.forms.index'));

        $this->get(route('patient.forms.index'))
            ->assertOk()
            ->assertSee('Forms')
            ->assertSee(FormTemplate::DEFAULT_NEW_PATIENT_INTAKE_NAME)
            ->assertSee('Complete form');
    }

    public function test_patient_dashboard_shows_pending_forms(): void
    {
        [, $admin, $patient] = $this->practiceAdminAndPatient();
        $this->pendingSubmissionFor($patient);
        $this->openPortalSessionFor($patient, $admin);

        $this->get(route('patient.dashboard'))
            ->assertOk()
            ->assertSee('Forms')
            ->assertSee(FormTemplate::DEFAULT_NEW_PATIENT_INTAKE_NAME)
            ->assertSee('Pending');
    }

    public function test_patient_can_submit_assigned_form(): void
    {
        [, $admin, $patient] = $this->practiceAdminAndPatient();
        $submission = $this->pendingSubmissionFor($patient);
        $originalDob = $patient->dob?->toDateString();
        $this->openPortalSessionFor($patient, $admin);

        $this->get(route('patient.forms.show', ['formSubmission' => $submission->id]))
            ->assertOk()
            ->assertSee('Main reason for visit');

        $this->post(route('patient.forms.store', ['formSubmission' => $submission->id]), [
            'fields' => [
                'date_of_birth' => '1984-03-02',
                'main_concern' => 'Sleep and stress support.',
                'health_history' => 'No major changes.',
                'current_medications' => 'None.',
                'consent_to_contact' => '1',
            ],
        ])->assertRedirect(route('patient.forms.index'));

        $submission->refresh();
        $this->assertSame(FormSubmission::STATUS_SUBMITTED, $submission->status);
        $this->assertSame('Sleep and stress support.', $submission->submitted_data_json['main_concern']);
        $this->assertTrue($submission->submitted_data_json['consent_to_contact']);

        $patient->refresh();
        $this->assertSame($originalDob, $patient->dob?->toDateString());
        $this->assertNotSame('Sleep and stress support.', $patient->notes);
    }

    public function test_patient_cannot_see_another_patients_form(): void
    {
        [$practice, $admin, $patient] = $this->practiceAdminAndPatient();
        $otherPatient = Patient::factory()->create(['practice_id' => $practice->id]);
        $otherSubmission = $this->pendingSubmissionFor($otherPatient);
        $this->openPortalSessionFor($patient, $admin);

        $this->get(route('patient.forms.show', ['formSubmission' => $otherSubmission->id]))
            ->assertNotFound();
    }

    public function test_cross_practice_patient_form_access_is_blocked(): void
    {
        [, $admin, $patient] = $this->practiceAdminAndPatient();
        $otherPractice = Practice::factory()->create();
        $otherPatient = Patient::factory()->create(['practice_id' => $otherPractice->id]);
        $otherSubmission = $this->pendingSubmissionFor($otherPatient);
        $this->openPortalSessionFor($patient, $admin);

        $this->get(route('patient.forms.show', ['formSubmission' => $otherSubmission->id]))
            ->assertNotFound();
    }

    public function test_staff_can_see_submitted_form_on_patient_view_and_review_or_archive(): void
    {
        [, $admin, $patient] = $this->practiceAdminAndPatient();
        $submission = $this->pendingSubmissionFor($patient);
        $submission->update([
            'status' => FormSubmission::STATUS_SUBMITTED,
            'submitted_data_json' => [
                'main_concern' => 'Low back stiffness.',
                'consent_to_contact' => true,
            ],
        ]);

        $this->actingAs($admin);

        $this->get(\App\Filament\Resources\Patients\PatientResource::getUrl('view', ['record' => $patient->id]))
            ->assertOk()
            ->assertSee('Portal Forms')
            ->assertSee('Low back stiffness.')
            ->assertSee('Consent To Contact');

        Livewire::test(ViewPatient::class, ['record' => $patient->id])
            ->callAction('mark_form_reviewed')
            ->assertHasNoActionErrors();
        $this->assertSame(FormSubmission::STATUS_REVIEWED, $submission->refresh()->status);

        Livewire::test(ViewPatient::class, ['record' => $patient->id])
            ->callAction('archive_form')
            ->assertHasNoActionErrors();
        $this->assertSame(FormSubmission::STATUS_ARCHIVED, $submission->refresh()->status);
    }

    private function pendingSubmissionFor(Patient $patient): FormSubmission
    {
        $template = FormTemplate::findOrCreateDefaultNewPatientIntake($patient->practice_id);

        return FormSubmission::withoutPracticeScope()->create([
            'practice_id' => $patient->practice_id,
            'patient_id' => $patient->id,
            'form_template_id' => $template->id,
            'status' => FormSubmission::STATUS_PENDING,
        ]);
    }

    private function openPortalSessionFor(Patient $patient, User $admin): void
    {
        [, $plainToken] = app(PatientPortalTokenService::class)->createForExistingPatient($patient, $admin);

        $this->get(route('patient.magic-link', ['token' => $plainToken]))
            ->assertRedirect(route('patient.dashboard'));
    }

    private function practiceAdminAndPatient(): array
    {
        $practice = Practice::factory()->create(['name' => 'Forms Clinic']);
        $admin = User::factory()->create(['practice_id' => $practice->id]);
        $admin->assignRole(User::ROLE_ADMINISTRATOR);
        $patient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => 'Nora',
            'last_name' => 'Forms',
            'email' => 'nora@example.test',
        ]);

        return [$practice, $admin, $patient];
    }
}
