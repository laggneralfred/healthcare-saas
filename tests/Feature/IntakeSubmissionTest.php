<?php

namespace Tests\Feature;

use App\Models\IntakeSubmission;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntakeSubmissionTest extends TestCase
{
    use RefreshDatabase;

    // ── Accessor tests (no DB needed) ─────────────────────────────────────────

    public function test_pain_scale_label_mild(): void
    {
        $submission = new IntakeSubmission();
        $submission->pain_scale = 2;
        $this->assertEquals('Mild', $submission->pain_scale_label);

        $submission->pain_scale = 0;
        $this->assertEquals('Mild', $submission->pain_scale_label);

        $submission->pain_scale = 3;
        $this->assertEquals('Mild', $submission->pain_scale_label);
    }

    public function test_pain_scale_label_severe(): void
    {
        $submission = new IntakeSubmission();
        $submission->pain_scale = 8;
        $this->assertEquals('Severe', $submission->pain_scale_label);

        $submission->pain_scale = 4;
        $this->assertEquals('Moderate', $submission->pain_scale_label);

        $submission->pain_scale = 10;
        $this->assertEquals('Worst Possible', $submission->pain_scale_label);

        $submission->pain_scale = null;
        $this->assertNull($submission->pain_scale_label);
    }

    public function test_onset_type_label(): void
    {
        $submission = new IntakeSubmission();

        $submission->onset_type = 'sudden';
        $this->assertEquals('Sudden / Acute', $submission->onset_type_label);

        $submission->onset_type = 'gradual';
        $this->assertEquals('Gradual / Chronic', $submission->onset_type_label);

        $submission->onset_type = 'recurring';
        $this->assertEquals('Recurring', $submission->onset_type_label);

        $submission->onset_type = null;
        $this->assertNull($submission->onset_type_label);
    }

    public function test_discipline_label(): void
    {
        $submission = new IntakeSubmission();

        $submission->discipline = 'acupuncture';
        $this->assertEquals('Acupuncture', $submission->discipline_label);

        $submission->discipline = 'massage';
        $this->assertEquals('Massage Therapy', $submission->discipline_label);

        $submission->discipline = 'chiropractic';
        $this->assertEquals('Chiropractic', $submission->discipline_label);

        $submission->discipline = 'physiotherapy';
        $this->assertEquals('Physiotherapy', $submission->discipline_label);
    }

    public function test_has_red_flags_true(): void
    {
        $submission = new IntakeSubmission();
        $submission->is_pregnant          = false;
        $submission->has_pacemaker        = true;
        $submission->takes_blood_thinners = false;
        $submission->has_bleeding_disorder = false;
        $submission->has_infectious_disease = false;

        $this->assertTrue($submission->hasRedFlags());

        // Also check each individual flag
        $submission->has_pacemaker = false;
        $submission->is_pregnant   = true;
        $this->assertTrue($submission->hasRedFlags());

        $submission->is_pregnant              = false;
        $submission->has_infectious_disease   = true;
        $this->assertTrue($submission->hasRedFlags());
    }

    public function test_has_red_flags_false(): void
    {
        $submission = new IntakeSubmission();
        $submission->is_pregnant            = false;
        $submission->has_pacemaker          = false;
        $submission->takes_blood_thinners   = false;
        $submission->has_bleeding_disorder  = false;
        $submission->has_infectious_disease = false;

        $this->assertFalse($submission->hasRedFlags());
    }

    // ── Scope tests (DB required) ─────────────────────────────────────────────

    public function test_scope_for_discipline(): void
    {
        $practice = Practice::factory()->create();
        $user     = User::factory()->create(['practice_id' => $practice->id]);
        $patient  = Patient::factory()->create(['practice_id' => $practice->id]);

        $this->actingAs($user);

        IntakeSubmission::factory()->count(2)->create([
            'practice_id' => $practice->id,
            'patient_id'  => $patient->id,
            'discipline'  => 'acupuncture',
        ]);
        IntakeSubmission::factory()->create([
            'practice_id' => $practice->id,
            'patient_id'  => $patient->id,
            'discipline'  => 'massage',
        ]);

        $this->assertEquals(2, IntakeSubmission::forDiscipline('acupuncture')->count());
        $this->assertEquals(1, IntakeSubmission::forDiscipline('massage')->count());
        $this->assertEquals(0, IntakeSubmission::forDiscipline('chiropractic')->count());
    }

    public function test_scope_with_red_flags(): void
    {
        $practice = Practice::factory()->create();
        $user     = User::factory()->create(['practice_id' => $practice->id]);
        $patient  = Patient::factory()->create(['practice_id' => $practice->id]);

        $this->actingAs($user);

        IntakeSubmission::factory()->create([
            'practice_id'   => $practice->id,
            'patient_id'    => $patient->id,
            'has_pacemaker' => true,
        ]);
        IntakeSubmission::factory()->create([
            'practice_id'   => $practice->id,
            'patient_id'    => $patient->id,
            'is_pregnant'   => true,
        ]);
        IntakeSubmission::factory()->create([
            'practice_id'   => $practice->id,
            'patient_id'    => $patient->id,
            'has_pacemaker' => false,
            'is_pregnant'   => false,
        ]);

        $this->assertEquals(2, IntakeSubmission::withRedFlags()->count());
    }

    public function test_scope_with_consent(): void
    {
        $practice = Practice::factory()->create();
        $user     = User::factory()->create(['practice_id' => $practice->id]);
        $patient  = Patient::factory()->create(['practice_id' => $practice->id]);

        $this->actingAs($user);

        IntakeSubmission::factory()->count(2)->create([
            'practice_id'   => $practice->id,
            'patient_id'    => $patient->id,
            'consent_given' => true,
        ]);
        IntakeSubmission::factory()->create([
            'practice_id'   => $practice->id,
            'patient_id'    => $patient->id,
            'consent_given' => false,
        ]);

        $this->assertEquals(2, IntakeSubmission::withConsent()->count());
    }

    public function test_scope_pending_consent(): void
    {
        $practice = Practice::factory()->create();
        $user     = User::factory()->create(['practice_id' => $practice->id]);
        $patient  = Patient::factory()->create(['practice_id' => $practice->id]);

        $this->actingAs($user);

        IntakeSubmission::factory()->create([
            'practice_id'   => $practice->id,
            'patient_id'    => $patient->id,
            'consent_given' => true,
        ]);
        IntakeSubmission::factory()->count(3)->create([
            'practice_id'   => $practice->id,
            'patient_id'    => $patient->id,
            'consent_given' => false,
        ]);

        $this->assertEquals(3, IntakeSubmission::pendingConsent()->count());
    }

    public function test_consent_auto_fills_signed_at(): void
    {
        $practice = Practice::factory()->create();
        $user     = User::factory()->create(['practice_id' => $practice->id]);
        $patient  = Patient::factory()->create(['practice_id' => $practice->id]);

        $this->actingAs($user);

        $submission = IntakeSubmission::factory()->create([
            'practice_id'   => $practice->id,
            'patient_id'    => $patient->id,
            'consent_given' => false,
        ]);

        $this->assertNull($submission->consent_signed_at);

        $submission->update(['consent_given' => true, 'consent_signed_by' => 'Jane Patient']);
        $submission->refresh();

        $this->assertTrue($submission->consent_given);
        $this->assertNotNull($submission->consent_signed_at);
    }

    public function test_multi_tenancy_isolation(): void
    {
        $practiceA = Practice::factory()->create();
        $practiceB = Practice::factory()->create();
        $userA     = User::factory()->create(['practice_id' => $practiceA->id]);
        $userB     = User::factory()->create(['practice_id' => $practiceB->id]);
        $patientA  = Patient::factory()->create(['practice_id' => $practiceA->id]);

        // Create an intake submission for practice A
        $this->actingAs($userA);
        IntakeSubmission::factory()->create([
            'practice_id' => $practiceA->id,
            'patient_id'  => $patientA->id,
        ]);
        $this->assertEquals(1, IntakeSubmission::count());

        // Switch to practice B — practice A's submission must be invisible
        $this->actingAs($userB);
        $this->assertEquals(0, IntakeSubmission::count());
    }
}
