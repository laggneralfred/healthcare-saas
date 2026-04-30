<?php

namespace Tests\Feature;

use App\Filament\Resources\Patients\Pages\CreatePatient;
use App\Filament\Resources\Patients\Pages\EditPatient;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\States\Appointment\NoShow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PatientDemographicsTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_name_accessor_returns_first_and_last(): void
    {
        $practice = Practice::factory()->create();

        $patient = Patient::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'first_name'  => 'Jane',
            'last_name'   => 'Smith',
            'name'        => 'Jane Smith',
            'is_patient'  => true,
        ]);

        $this->assertSame('Jane Smith', $patient->full_name);
    }

    public function test_name_auto_populated_from_first_last_on_save(): void
    {
        $practice = Practice::factory()->create();

        $patient = Patient::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'first_name'  => 'John',
            'last_name'   => 'Doe',
            'is_patient'  => true,
        ]);

        $this->assertSame('John Doe', $patient->name);
    }

    public function test_new_demographic_fields_are_fillable(): void
    {
        $practice = Practice::factory()->create();

        $patient = Patient::withoutPracticeScope()->create([
            'practice_id'                  => $practice->id,
            'first_name'                   => 'Alice',
            'last_name'                    => 'Walker',
            'middle_name'                  => 'Marie',
            'preferred_name'               => 'Ali',
            'pronouns'                     => 'She/Her',
            'address_line_1'               => '123 Main St',
            'address_line_2'               => 'Apt 4B',
            'city'                         => 'Portland',
            'state'                        => 'OR',
            'postal_code'                  => '97201',
            'country'                      => 'USA',
            'emergency_contact_name'       => 'Bob Walker',
            'emergency_contact_phone'      => '5035551234',
            'emergency_contact_relationship' => 'Spouse',
            'occupation'                   => 'Teacher',
            'referred_by'                  => 'Google',
            'preferred_language'           => 'Spanish',
            'is_patient'                   => true,
        ]);

        $this->assertSame('Alice Walker', $patient->name);
        $this->assertSame('Marie', $patient->middle_name);
        $this->assertSame('Ali', $patient->preferred_name);
        $this->assertSame('She/Her', $patient->pronouns);
        $this->assertSame('es', $patient->preferred_language);
        $this->assertSame('Spanish', $patient->preferred_language_label);
        $this->assertSame('123 Main St', $patient->address_line_1);
        $this->assertSame('OR', $patient->state);
    }

    public function test_preferred_language_defaults_to_english(): void
    {
        $practice = Practice::factory()->create();

        $patient = Patient::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'first_name' => 'Default',
            'last_name' => 'Language',
            'is_patient' => true,
        ])->refresh();

        $this->assertSame('en', $patient->preferred_language);
        $this->assertSame('English', $patient->preferred_language_label);
    }

    public function test_patient_can_be_created_with_preferred_language_from_form(): void
    {
        $practice = Practice::factory()->create();
        $admin = User::factory()->create(['practice_id' => $practice->id]);

        $this->actingAs($admin);

        Livewire::test(CreatePatient::class)
            ->assertSee('Preferred Language')
            ->assertSee('Used for reminders, follow-ups, and patient-facing messages.')
            ->set('data.first_name', 'Maria')
            ->set('data.last_name', 'Garcia')
            ->set('data.email', 'maria.language@example.test')
            ->set('data.preferred_language', 'es')
            ->call('create');

        $this->assertDatabaseHas('patients', [
            'practice_id' => $practice->id,
            'email' => 'maria.language@example.test',
            'preferred_language' => 'es',
        ]);
    }

    public function test_patient_can_be_updated_with_preferred_language_from_form(): void
    {
        $practice = Practice::factory()->create();
        $admin = User::factory()->create(['practice_id' => $practice->id]);
        $patient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'preferred_language' => 'en',
        ]);

        $this->actingAs($admin);

        Livewire::test(EditPatient::class, ['record' => $patient->id])
            ->fillForm([
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'email' => $patient->email,
                'phone' => null,
                'emergency_contact_phone' => null,
                'preferred_language' => 'vi',
            ])
            ->assertSet('data.preferred_language', 'vi')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('vi', $patient->refresh()->preferred_language);
        $this->assertSame('Vietnamese', $patient->preferred_language_label);
    }

    public function test_patient_view_shows_preferred_language_subtly(): void
    {
        $practice = Practice::factory()->create();
        $admin = User::factory()->create(['practice_id' => $practice->id]);
        $patient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'preferred_language' => 'fr',
        ]);

        $this->actingAs($admin)
            ->get("/admin/patients/{$patient->id}")
            ->assertSuccessful()
            ->assertSee('French')
            ->assertSee('Care Status: New')
            ->assertSee('Care status helps you see who may need attention or a gentle follow-up.');
    }

    public function test_patient_list_shows_care_status_badge_and_preferred_language(): void
    {
        $practice = Practice::factory()->create();
        $admin = User::factory()->create(['practice_id' => $practice->id]);
        $patient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => 'Care',
            'last_name' => 'Status',
            'preferred_language' => 'es',
        ]);
        $practitioner = Practitioner::factory()->create(['practice_id' => $practice->id]);
        $appointmentType = AppointmentType::factory()->create(['practice_id' => $practice->id]);

        Appointment::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'appointment_type_id' => $appointmentType->id,
            'status' => NoShow::$name,
            'start_datetime' => now()->subDays(2),
            'end_datetime' => now()->subDays(2)->addHour(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/patients')
            ->assertSuccessful()
            ->assertSee('Care Status')
            ->assertSee('At Risk')
            ->assertSee('Spanish');
    }
}
