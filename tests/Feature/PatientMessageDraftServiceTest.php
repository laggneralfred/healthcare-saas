<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\User;
use App\Services\PatientMessageDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientMessageDraftServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_english_invite_back_draft_uses_patient_and_sender_names(): void
    {
        $practice = Practice::factory()->create(['name' => 'Practiq Care']);
        $user = User::factory()->create(['name' => 'Dr. Lee', 'practice_id' => $practice->id]);
        $practitioner = Practitioner::factory()->create([
            'practice_id' => $practice->id,
            'user_id' => $user->id,
        ]);
        $patient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => 'Nora',
            'preferred_language' => 'en',
        ]);

        $draft = app(PatientMessageDraftService::class)->inviteBack($patient, $practice, $practitioner);

        $this->assertSame('invite_back', $draft['type']);
        $this->assertSame('en', $draft['language_code']);
        $this->assertSame('English', $draft['language_label']);
        $this->assertSame('Checking in', $draft['subject']);
        $this->assertStringContainsString('Hi Nora,', $draft['body']);
        $this->assertStringContainsString('Warmly,' . PHP_EOL . 'Dr. Lee', $draft['body']);
        $this->assertSame($draft['english_body'], $draft['body']);
        $this->assertFalse($draft['is_localized']);
        $this->assertFalse($draft['fallback_used']);
    }

    public function test_patient_first_name_falls_back_to_full_name_or_there(): void
    {
        $practice = Practice::factory()->create(['name' => 'Practiq Care']);
        $namedPatient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => null,
            'last_name' => null,
            'preferred_name' => null,
            'name' => 'Avery Patient',
            'preferred_language' => 'en',
        ]);
        $unnamedPatient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => null,
            'last_name' => null,
            'preferred_name' => null,
            'name' => '',
            'preferred_language' => 'en',
        ]);

        $service = app(PatientMessageDraftService::class);

        $this->assertStringContainsString('Hi Avery,', $service->inviteBack($namedPatient, $practice)['body']);
        $this->assertStringContainsString('Hi there,', $service->inviteBack($unnamedPatient, $practice)['body']);
    }

    public function test_sender_name_falls_back_to_practice_or_care_team(): void
    {
        $practice = Practice::factory()->create(['name' => 'Quiet Clinic']);
        $patient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => 'Nora',
            'preferred_language' => 'en',
        ]);

        $service = app(PatientMessageDraftService::class);

        $this->assertStringContainsString('Warmly,' . PHP_EOL . 'Quiet Clinic', $service->inviteBack($patient, $practice)['body']);
        $this->assertStringContainsString('Warmly,' . PHP_EOL . 'your care team', $service->inviteBack($patient)['body']);
    }

    public function test_spanish_preferred_language_uses_deterministic_spanish_template(): void
    {
        $practice = Practice::factory()->create(['name' => 'Practiq Care']);
        $patient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => 'Nora',
            'preferred_language' => 'es',
        ]);

        $draft = app(PatientMessageDraftService::class)->inviteBack($patient, $practice);

        $this->assertSame('es', $draft['language_code']);
        $this->assertSame('Spanish', $draft['language_label']);
        $this->assertStringContainsString('Hola Nora,', $draft['body']);
        $this->assertStringContainsString('Con aprecio,' . PHP_EOL . 'Practiq Care', $draft['body']);
        $this->assertNotSame($draft['english_body'], $draft['localized_body']);
        $this->assertTrue($draft['is_localized']);
        $this->assertFalse($draft['fallback_used']);
    }

    public function test_unsupported_language_falls_back_to_english(): void
    {
        $practice = Practice::factory()->create(['name' => 'Practiq Care']);
        $patient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => 'Nora',
            'preferred_language' => 'fr',
        ]);

        $draft = app(PatientMessageDraftService::class)->inviteBack($patient, $practice);

        $this->assertSame('fr', $draft['language_code']);
        $this->assertSame('French', $draft['language_label']);
        $this->assertSame($draft['english_body'], $draft['body']);
        $this->assertSame($draft['english_body'], $draft['localized_body']);
        $this->assertFalse($draft['is_localized']);
        $this->assertTrue($draft['fallback_used']);
    }
}
