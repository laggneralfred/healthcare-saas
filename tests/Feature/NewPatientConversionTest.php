<?php

namespace Tests\Feature;

use App\Filament\Resources\NewPatientInterests\NewPatientInterestResource;
use App\Filament\Resources\NewPatientInterests\Pages\ViewNewPatientInterest;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\NewPatientInterest;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\User;
use App\Services\NewPatientInterestConversionService;
use App\Services\PatientPortalTokenService;
use App\Support\PracticeAccessRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class NewPatientConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PracticeAccessRoles::ensureRoles();
    }

    public function test_staff_can_convert_new_patient_interest_into_patient(): void
    {
        [$practice, $admin, $interest] = $this->practiceAdminAndInterest();
        $submission = $this->submittedFormFor($interest, [
            'date_of_birth' => '1988-04-12',
            'preferred_language' => 'es',
            'main_concern' => 'Neck tension.',
        ]);

        $this->actingAs($admin);

        Livewire::test(ViewNewPatientInterest::class, ['record' => $interest->id])
            ->callAction('create_patient')
            ->assertHasNoActionErrors();

        $patient = Patient::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('email', $interest->email)
            ->firstOrFail();

        $this->assertSame('Nora', $patient->first_name);
        $this->assertSame('Interested', $patient->last_name);
        $this->assertSame('Nora Interested', $patient->name);
        $this->assertSame($interest->email, $patient->email);
        $this->assertSame('+15550101234', $patient->getRawOriginal('phone'));
        $this->assertSame('1988-04-12', $patient->dob->toDateString());
        $this->assertSame(Patient::LANGUAGE_SPANISH, $patient->preferred_language);

        $interest->refresh();
        $this->assertSame(NewPatientInterest::STATUS_CONVERTED, $interest->status);
        $this->assertSame($patient->id, $interest->converted_patient_id);
        $this->assertSame($admin->id, $interest->responded_by_user_id);
        $this->assertNotNull($interest->responded_at);

        $submission->refresh();
        $this->assertSame($patient->id, $submission->patient_id);
        $this->assertSame($interest->id, $submission->new_patient_interest_id);
    }

    public function test_conversion_maps_supported_submitted_form_demographics(): void
    {
        [, $admin, $interest] = $this->practiceAdminAndInterest();
        $this->submittedFormFor($interest, [
            'dob' => '1990-02-03',
            'gender' => 'Female',
            'address' => '123 Calm Street',
            'address2' => 'Suite 4',
            'city' => 'Portland',
            'state' => 'OR',
            'zip' => '97201',
            'preferred_language' => 'Vietnamese',
            'emergency_contact_name' => 'Casey Contact',
            'occupation' => 'Teacher',
        ]);

        $patient = app(NewPatientInterestConversionService::class)->convert($interest, $admin);

        $this->assertSame('1990-02-03', $patient->dob->toDateString());
        $this->assertSame('Female', $patient->gender);
        $this->assertSame('123 Calm Street', $patient->address_line_1);
        $this->assertSame('Suite 4', $patient->address_line_2);
        $this->assertSame('Portland', $patient->city);
        $this->assertSame('OR', $patient->state);
        $this->assertSame('97201', $patient->postal_code);
        $this->assertSame(Patient::LANGUAGE_VIETNAMESE, $patient->preferred_language);
        $this->assertSame('Casey Contact', $patient->emergency_contact_name);
        $this->assertSame('Teacher', $patient->occupation);
    }

    public function test_form_submit_does_not_automatically_create_patient(): void
    {
        [, $admin, $interest] = $this->practiceAdminAndInterest();
        $this->pendingSubmissionFor($interest);
        [, $plainToken] = app(PatientPortalTokenService::class)->createForNewPatientInterest($interest, $admin);

        $this->post(route('patient.new-patient-form.store', ['token' => $plainToken]), [
            'fields' => [
                'main_concern' => 'Stress and sleep.',
                'consent_to_contact' => '1',
            ],
        ])->assertRedirect(route('patient.new-patient-form.thanks'));

        $this->assertSame(0, Patient::withoutPracticeScope()->count());
        $this->assertSame(NewPatientInterest::STATUS_REVIEWING, $interest->refresh()->status);
        $this->assertNull($interest->converted_patient_id);
    }

    public function test_duplicate_conversion_is_prevented(): void
    {
        [, $admin, $interest] = $this->practiceAdminAndInterest();

        $firstPatient = app(NewPatientInterestConversionService::class)->convert($interest, $admin);

        try {
            app(NewPatientInterestConversionService::class)->convert($interest->refresh(), $admin);
            $this->fail('Expected duplicate conversion to be blocked.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('already been converted', collect($exception->errors())->flatten()->first());
        }

        $this->assertSame(1, Patient::withoutPracticeScope()->where('practice_id', $interest->practice_id)->count());
        $this->assertSame($firstPatient->id, $interest->refresh()->converted_patient_id);
    }

    public function test_same_practice_patient_with_same_email_blocks_conversion(): void
    {
        [$practice, $admin, $interest] = $this->practiceAdminAndInterest();
        Patient::factory()->create([
            'practice_id' => $practice->id,
            'email' => $interest->email,
        ]);

        $this->actingAs($admin);

        Livewire::test(ViewNewPatientInterest::class, ['record' => $interest->id])
            ->callAction('create_patient')
            ->assertHasNoActionErrors();

        $this->assertSame(1, Patient::withoutPracticeScope()->where('practice_id', $practice->id)->count());
        $this->assertNull($interest->refresh()->converted_patient_id);
        $this->assertNotSame(NewPatientInterest::STATUS_CONVERTED, $interest->status);
    }

    public function test_same_email_in_another_practice_does_not_block_conversion(): void
    {
        [, $admin, $interest] = $this->practiceAdminAndInterest();
        $otherPractice = Practice::factory()->create();
        Patient::factory()->create([
            'practice_id' => $otherPractice->id,
            'email' => $interest->email,
        ]);

        $patient = app(NewPatientInterestConversionService::class)->convert($interest, $admin);

        $this->assertSame($interest->practice_id, $patient->practice_id);
        $this->assertSame(2, Patient::withoutPracticeScope()->where('email', $interest->email)->count());
    }

    public function test_cross_practice_staff_cannot_convert_another_practices_interest(): void
    {
        [, $admin] = $this->practiceWithAdmin();
        [$otherPractice] = $this->practiceWithAdmin();
        $otherInterest = $this->interestFor($otherPractice);

        $this->actingAs($admin);

        $this->get(NewPatientInterestResource::getUrl('view', ['record' => $otherInterest->id]))
            ->assertNotFound();

        $this->assertSame(0, Patient::withoutPracticeScope()->count());
    }

    public function test_declined_closed_and_converted_interests_cannot_be_converted_again(): void
    {
        [, $admin, $interest] = $this->practiceAdminAndInterest();
        $declined = $this->interestFor($interest->practice, [
            'email' => 'declined@example.test',
            'status' => NewPatientInterest::STATUS_DECLINED,
        ]);
        $closed = $this->interestFor($interest->practice, [
            'email' => 'closed@example.test',
            'status' => NewPatientInterest::STATUS_CLOSED,
        ]);

        foreach ([$declined, $closed] as $blocked) {
            try {
                app(NewPatientInterestConversionService::class)->convert($blocked, $admin);
                $this->fail('Expected blocked interest status to be rejected.');
            } catch (ValidationException $exception) {
                $this->assertStringContainsString('cannot be converted', collect($exception->errors())->flatten()->first());
            }
        }

        $convertedPatient = app(NewPatientInterestConversionService::class)->convert($interest, $admin);
        $this->assertSame($convertedPatient->id, $interest->refresh()->converted_patient_id);

        try {
            app(NewPatientInterestConversionService::class)->convert($interest, $admin);
            $this->fail('Expected converted interest to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('already been converted', collect($exception->errors())->flatten()->first());
        }
    }

    private function submittedFormFor(NewPatientInterest $interest, array $data): FormSubmission
    {
        $submission = $this->pendingSubmissionFor($interest);
        $submission->update([
            'status' => FormSubmission::STATUS_SUBMITTED,
            'submitted_data_json' => $data,
        ]);

        return $submission;
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
