<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Practice;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'is_patient'                   => true,
        ]);

        $this->assertSame('Alice Walker', $patient->name);
        $this->assertSame('Marie', $patient->middle_name);
        $this->assertSame('Ali', $patient->preferred_name);
        $this->assertSame('She/Her', $patient->pronouns);
        $this->assertSame('123 Main St', $patient->address_line_1);
        $this->assertSame('OR', $patient->state);
    }
}
