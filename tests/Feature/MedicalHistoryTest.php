<?php

namespace Tests\Feature;

use App\Filament\Resources\MedicalHistories\Pages\CreateMedicalHistory;
use App\Filament\Resources\MedicalHistories\Pages\EditMedicalHistory;
use App\Filament\Resources\MedicalHistories\Pages\ViewMedicalHistory;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\User;
use App\Support\PracticeAccessRoles;
use App\Support\PracticeType;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class MedicalHistoryTest extends TestCase
{
    use RefreshDatabase;

    // ── Accessor tests (no DB needed) ─────────────────────────────────────────

    public function test_medical_histories_table_has_nullable_practitioner_id(): void
    {
        $this->assertTrue(Schema::hasColumn('medical_histories', 'practitioner_id'));

        $practice = Practice::factory()->create();
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);
        $submission = MedicalHistory::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'practitioner_id' => null,
        ]);

        $this->assertNull($submission->fresh()->practitioner_id);
    }

    public function test_pain_scale_label_mild(): void
    {
        $submission = new MedicalHistory;
        $submission->pain_scale = 2;
        $this->assertEquals('Mild', $submission->pain_scale_label);

        $submission->pain_scale = 0;
        $this->assertEquals('Mild', $submission->pain_scale_label);

        $submission->pain_scale = 3;
        $this->assertEquals('Mild', $submission->pain_scale_label);
    }

    public function test_pain_scale_label_severe(): void
    {
        $submission = new MedicalHistory;
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
        $submission = new MedicalHistory;

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
        $submission = new MedicalHistory;

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
        $submission = new MedicalHistory;
        $submission->is_pregnant = false;
        $submission->has_pacemaker = true;
        $submission->takes_blood_thinners = false;
        $submission->has_bleeding_disorder = false;
        $submission->has_infectious_disease = false;

        $this->assertTrue($submission->hasRedFlags());

        // Also check each individual flag
        $submission->has_pacemaker = false;
        $submission->is_pregnant = true;
        $this->assertTrue($submission->hasRedFlags());

        $submission->is_pregnant = false;
        $submission->has_infectious_disease = true;
        $this->assertTrue($submission->hasRedFlags());
    }

    public function test_has_red_flags_false(): void
    {
        $submission = new MedicalHistory;
        $submission->is_pregnant = false;
        $submission->has_pacemaker = false;
        $submission->takes_blood_thinners = false;
        $submission->has_bleeding_disorder = false;
        $submission->has_infectious_disease = false;

        $this->assertFalse($submission->hasRedFlags());
    }

    // ── Scope tests (DB required) ─────────────────────────────────────────────

    public function test_scope_for_discipline(): void
    {
        $practice = Practice::factory()->create();
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);

        $this->actingAs($user);

        MedicalHistory::factory()->count(2)->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'discipline' => 'acupuncture',
        ]);
        MedicalHistory::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'discipline' => 'massage',
        ]);

        $this->assertEquals(2, MedicalHistory::forDiscipline('acupuncture')->count());
        $this->assertEquals(1, MedicalHistory::forDiscipline('massage')->count());
        $this->assertEquals(0, MedicalHistory::forDiscipline('chiropractic')->count());
    }

    public function test_scope_with_red_flags(): void
    {
        $practice = Practice::factory()->create();
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);

        $this->actingAs($user);

        MedicalHistory::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'has_pacemaker' => true,
        ]);
        MedicalHistory::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'is_pregnant' => true,
        ]);
        MedicalHistory::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'has_pacemaker' => false,
            'is_pregnant' => false,
        ]);

        $this->assertEquals(2, MedicalHistory::withRedFlags()->count());
    }

    public function test_scope_with_consent(): void
    {
        $practice = Practice::factory()->create();
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);

        $this->actingAs($user);

        MedicalHistory::factory()->count(2)->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'consent_given' => true,
        ]);
        MedicalHistory::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'consent_given' => false,
        ]);

        $this->assertEquals(2, MedicalHistory::withConsent()->count());
    }

    public function test_scope_pending_consent(): void
    {
        $practice = Practice::factory()->create();
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);

        $this->actingAs($user);

        MedicalHistory::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'consent_given' => true,
        ]);
        MedicalHistory::factory()->count(3)->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'consent_given' => false,
        ]);

        $this->assertEquals(3, MedicalHistory::pendingConsent()->count());
    }

    public function test_consent_auto_fills_signed_at(): void
    {
        $practice = Practice::factory()->create();
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);

        $this->actingAs($user);

        $submission = MedicalHistory::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
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
        $userA = User::factory()->create(['practice_id' => $practiceA->id]);
        $userB = User::factory()->create(['practice_id' => $practiceB->id]);
        $patientA = Patient::factory()->create(['practice_id' => $practiceA->id]);

        // Create an intake submission for practice A
        $this->actingAs($userA);
        MedicalHistory::factory()->create([
            'practice_id' => $practiceA->id,
            'patient_id' => $patientA->id,
        ]);
        $this->assertEquals(1, MedicalHistory::count());

        // Switch to practice B — practice A's submission must be invisible
        $this->actingAs($userB);
        $this->assertEquals(0, MedicalHistory::count());
    }

    // ── Part B: Discipline-specific data tests ────────────────────────────────

    public function test_discipline_responses_stores_tcm_data(): void
    {
        $practice = Practice::factory()->create();
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);

        $this->actingAs($user);

        $tcmData = [
            'energy_level' => 'low',
            'temperature_preference' => 'cold',
            'sleep_issues' => ['staying_asleep', 'night_sweats'],
            'emotional_tendencies' => ['stress', 'anxiety'],
        ];

        $submission = MedicalHistory::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'discipline' => 'acupuncture',
            'discipline_responses' => ['tcm' => $tcmData],
        ]);

        $submission->refresh();
        $this->assertEquals('low', $submission->discipline_responses['tcm']['energy_level']);
        $this->assertEquals(['staying_asleep', 'night_sweats'], $submission->discipline_responses['tcm']['sleep_issues']);
        $this->assertEquals(['stress', 'anxiety'], $submission->discipline_responses['tcm']['emotional_tendencies']);
    }

    public function test_discipline_responses_stores_massage_data(): void
    {
        $practice = Practice::factory()->create();
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);

        $this->actingAs($user);

        $massageData = [
            'focus_areas' => ['neck', 'shoulders', 'upper_back'],
            'pressure_preference' => 'firm',
            'session_goals' => ['pain_relief', 'relaxation'],
        ];

        $submission = MedicalHistory::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'discipline' => 'massage',
            'discipline_responses' => ['massage' => $massageData],
        ]);

        $submission->refresh();
        $this->assertEquals('firm', $submission->discipline_responses['massage']['pressure_preference']);
        $this->assertContains('neck', $submission->discipline_responses['massage']['focus_areas']);
    }

    public function test_discipline_responses_stores_chiro_data(): void
    {
        $practice = Practice::factory()->create();
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);

        $this->actingAs($user);

        $chiroData = [
            'pain_locations' => ['lower_back', 'hip'],
            'pain_character' => ['dull', 'stiffness'],
            'onset_mechanism' => 'gradual',
            'adjustment_consent' => 'comfortable',
        ];

        $submission = MedicalHistory::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'discipline' => 'chiropractic',
            'discipline_responses' => ['chiro' => $chiroData],
        ]);

        $submission->refresh();
        $this->assertEquals('comfortable', $submission->discipline_responses['chiro']['adjustment_consent']);
        $this->assertContains('lower_back', $submission->discipline_responses['chiro']['pain_locations']);
    }

    public function test_discipline_responses_stores_physio_data(): void
    {
        $practice = Practice::factory()->create();
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);

        $this->actingAs($user);

        $physioData = [
            'functional_limitations' => 'Cannot climb stairs without significant pain.',
            'work_status' => 'modified',
            'functional_goals' => ['return_work', 'pain_reduction'],
            'timeline_expectation' => 'months',
        ];

        $submission = MedicalHistory::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'discipline' => 'physiotherapy',
            'discipline_responses' => ['physio' => $physioData],
        ]);

        $submission->refresh();
        $this->assertEquals('modified', $submission->discipline_responses['physio']['work_status']);
        $this->assertContains('return_work', $submission->discipline_responses['physio']['functional_goals']);
    }

    public function test_get_discipline_responses_for_nested_value(): void
    {
        $submission = new MedicalHistory;
        $submission->discipline_responses = [
            'tcm' => ['energy_level' => 'low', 'thirst' => 'high'],
        ];

        $this->assertEquals('low', $submission->getDisciplineResponsesFor('tcm.energy_level'));
        $this->assertEquals('high', $submission->getDisciplineResponsesFor('tcm.thirst'));
        $this->assertNull($submission->getDisciplineResponsesFor('tcm.nonexistent'));
        $this->assertNull($submission->getDisciplineResponsesFor('massage.focus_areas'));
    }

    public function test_practice_without_discipline_defaults_to_acupuncture(): void
    {
        $practice = Practice::factory()->create(['discipline' => null]);
        $user = User::factory()->create(['practice_id' => $practice->id]);

        // The intake form's discipline field defaults to 'acupuncture' when practice discipline is null
        $defaultDiscipline = $user->practice?->discipline ?? 'acupuncture';

        $this->assertEquals('acupuncture', $defaultDiscipline);

        // A practice WITH discipline should return that discipline
        $practice2 = Practice::factory()->create(['discipline' => 'massage']);
        $user2 = User::factory()->create(['practice_id' => $practice2->id]);

        $defaultDiscipline2 = $user2->practice?->discipline ?? 'acupuncture';
        $this->assertEquals('massage', $defaultDiscipline2);
    }

    public function test_get_discipline_section_returns_formatted_data(): void
    {
        $submission = new MedicalHistory;
        $submission->discipline = 'acupuncture';
        $submission->discipline_responses = ['tcm' => ['energy_level' => 'low']];

        $section = $submission->getDisciplineSection();
        $this->assertEquals('Acupuncture Intake', $section['label']);
        $this->assertEquals('tcm', $section['key']);
        $this->assertEquals(['energy_level' => 'low'], $section['data']);

        $submission->discipline = 'massage';
        $submission->discipline_responses = ['massage' => ['pressure_preference' => 'firm']];
        $section = $submission->getDisciplineSection();
        $this->assertEquals('Massage Preferences', $section['label']);

        $submission->discipline = null;
        $section = $submission->getDisciplineSection();
        $this->assertEquals('Additional Information', $section['label']);
    }

    public function test_demo_seeder_creates_medical_historys_with_tcm_data(): void
    {
        $this->seed(DemoSeeder::class);

        $tcmSubmissions = MedicalHistory::withoutPracticeScope()
            ->where('discipline', 'acupuncture')
            ->whereNotNull('discipline_responses')
            ->get()
            ->filter(fn ($s) => ! empty(data_get($s->discipline_responses, 'tcm.energy_level')));

        $this->assertGreaterThanOrEqual(3, $tcmSubmissions->count(),
            'DemoSeeder should create at least 3 acupuncture intakes with TCM energy_level data');

        $first = $tcmSubmissions->first();
        $this->assertNotNull($first->chief_complaint);
        $this->assertNotNull($first->discipline_responses['tcm']['emotional_tendencies']);
    }

    public function test_view_medical_history_page_loads(): void
    {
        $practice = Practice::factory()->create();
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);

        $this->actingAs($user);
        $submission = MedicalHistory::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
        ]);

        $response = $this->get("/admin/medical-histories/{$submission->id}");
        $response->assertSuccessful();
    }

    public function test_intake_form_create_page_loads(): void
    {
        $practice = Practice::factory()->create(['discipline' => 'acupuncture']);
        $user = User::factory()->create(['practice_id' => $practice->id]);

        $response = $this->actingAs($user)->get('/admin/medical-histories/create');
        $response->assertSuccessful();
    }

    public function test_owner_can_assign_practitioner_to_medical_history(): void
    {
        PracticeAccessRoles::ensureRoles();

        $practice = Practice::factory()->create();
        $owner = User::factory()->create(['practice_id' => $practice->id]);
        $owner->assignRole(User::ROLE_OWNER);
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);
        $practitioner = Practitioner::factory()->create(['practice_id' => $practice->id]);

        $this->actingAs($owner);

        Livewire::test(CreateMedicalHistory::class)
            ->set('data.patient_id', $patient->id)
            ->set('data.practitioner_id', $practitioner->id)
            ->set('data.status', 'pending')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('medical_histories', [
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
        ]);
    }

    public function test_administrator_can_assign_practitioner_to_medical_history(): void
    {
        PracticeAccessRoles::ensureRoles();

        $practice = Practice::factory()->create();
        $administrator = User::factory()->create(['practice_id' => $practice->id]);
        $administrator->assignRole(User::ROLE_ADMINISTRATOR);
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);
        $practitioner = Practitioner::factory()->create(['practice_id' => $practice->id]);

        $this->actingAs($administrator);

        Livewire::test(CreateMedicalHistory::class)
            ->set('data.patient_id', $patient->id)
            ->set('data.practitioner_id', $practitioner->id)
            ->set('data.status', 'pending')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('medical_histories', [
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
        ]);
    }

    public function test_practitioner_select_only_shows_practitioners_from_current_practice(): void
    {
        $practice = Practice::factory()->create();
        $otherPractice = Practice::factory()->create();
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $practitionerUser = User::factory()->create(['practice_id' => $practice->id, 'name' => 'Current Practice Doctor']);
        $otherUser = User::factory()->create(['practice_id' => $otherPractice->id, 'name' => 'Other Practice Doctor']);
        Practitioner::factory()->create(['practice_id' => $practice->id, 'user_id' => $practitionerUser->id]);
        Practitioner::factory()->create(['practice_id' => $otherPractice->id, 'user_id' => $otherUser->id]);

        $this->actingAs($user);

        Livewire::test(CreateMedicalHistory::class)
            ->assertSee('Assigned Practitioner')
            ->assertSee('Current Practice Doctor')
            ->assertDontSee('Other Practice Doctor');
    }

    public function test_cross_practice_practitioner_cannot_be_assigned(): void
    {
        $practice = Practice::factory()->create();
        $otherPractice = Practice::factory()->create();
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);
        $otherPractitioner = Practitioner::factory()->create(['practice_id' => $otherPractice->id]);

        $this->actingAs($user);

        Livewire::test(CreateMedicalHistory::class)
            ->set('data.patient_id', $patient->id)
            ->set('data.practitioner_id', $otherPractitioner->id)
            ->set('data.status', 'pending')
            ->call('create')
            ->assertHasErrors(['data.practitioner_id']);
    }

    public function test_cross_practice_practitioner_cannot_be_assigned_on_edit(): void
    {
        $practice = Practice::factory()->create();
        $otherPractice = Practice::factory()->create();
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);
        $submission = MedicalHistory::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
        ]);
        $otherPractitioner = Practitioner::factory()->create(['practice_id' => $otherPractice->id]);

        $this->actingAs($user);

        Livewire::test(EditMedicalHistory::class, ['record' => $submission->id])
            ->set('data.practitioner_id', $otherPractitioner->id)
            ->call('save')
            ->assertHasErrors(['data.practitioner_id']);
    }

    public function test_edit_form_assigned_practitioner_updates_intake_heading_before_save(): void
    {
        $practice = Practice::factory()->create([
            'practice_type' => PracticeType::TCM_ACUPUNCTURE,
            'discipline' => 'acupuncture',
        ]);
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);
        $fiveElementPractitioner = Practitioner::factory()->create([
            'practice_id' => $practice->id,
            'clinical_style' => PracticeType::FIVE_ELEMENT_ACUPUNCTURE,
        ]);
        $submission = MedicalHistory::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'practitioner_id' => null,
            'discipline' => 'acupuncture',
            'discipline_responses' => [
                'tcm' => ['energy_level' => 'low'],
            ],
        ]);

        $this->actingAs($user);

        Livewire::test(EditMedicalHistory::class, ['record' => $submission->id])
            ->assertSee('TCM Acupuncture Intake')
            ->set('data.practitioner_id', $fiveElementPractitioner->id)
            ->assertSee('Five Element Acupuncture Intake')
            ->assertDontSee('TCM Acupuncture Intake');

        $this->assertNull($submission->fresh()->practitioner_id);
    }

    public function test_five_element_intake_form_shows_read_only_practice_type_context(): void
    {
        $practice = Practice::factory()->create([
            'practice_type' => PracticeType::FIVE_ELEMENT_ACUPUNCTURE,
            'discipline' => 'acupuncture',
        ]);
        $user = User::factory()->create(['practice_id' => $practice->id]);

        $this->actingAs($user);

        Livewire::test(CreateMedicalHistory::class)
            ->assertSee('Practice Type')
            ->assertSee('Five Element Acupuncture')
            ->assertSee('Five Element Acupuncture Intake')
            ->assertDontSee('TCM Acupuncture')
            ->assertDontSee('Acupuncture & TCM Assessment')
            ->assertDontSee('Constitutional Factor')
            ->assertDontSee('Color / Sound / Odor')
            ->assertDontSee('Officials')
            ->assertDontSee('Treatment Intention')
            ->assertDontSee('Used to customize intake context, visit note templates, and AI suggestions.')
            ->assertDontSee('Acupuncture / TCM');
    }

    public function test_tcm_intake_form_shows_tcm_compatible_heading(): void
    {
        $practice = Practice::factory()->create([
            'practice_type' => PracticeType::TCM_ACUPUNCTURE,
            'discipline' => 'acupuncture',
        ]);
        $user = User::factory()->create(['practice_id' => $practice->id]);

        $this->actingAs($user);

        Livewire::test(CreateMedicalHistory::class)
            ->assertSee('Practice Type')
            ->assertSee('TCM Acupuncture')
            ->assertSee('TCM Acupuncture Intake')
            ->assertDontSee('Acupuncture & TCM Assessment');
    }

    public function test_five_element_saved_intake_view_does_not_show_tcm_assessment(): void
    {
        $practice = Practice::factory()->create([
            'practice_type' => PracticeType::FIVE_ELEMENT_ACUPUNCTURE,
            'discipline' => 'acupuncture',
        ]);
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);
        $submission = MedicalHistory::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'discipline' => 'acupuncture',
            'discipline_responses' => [
                'tcm' => ['energy_level' => 'low'],
            ],
        ]);

        $this->actingAs($user);

        Livewire::test(ViewMedicalHistory::class, ['record' => $submission->id])
            ->assertSee('Five Element Acupuncture Intake')
            ->assertDontSee('TCM Assessment');
    }

    public function test_assigned_five_element_practitioner_overrides_tcm_practice_default_for_intake_label(): void
    {
        $practice = Practice::factory()->create([
            'practice_type' => PracticeType::TCM_ACUPUNCTURE,
            'discipline' => 'acupuncture',
        ]);
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $practitionerUser = User::factory()->create(['practice_id' => $practice->id, 'name' => 'Dr Five']);
        $practitioner = Practitioner::factory()->create([
            'practice_id' => $practice->id,
            'user_id' => $practitionerUser->id,
            'clinical_style' => PracticeType::FIVE_ELEMENT_ACUPUNCTURE,
        ]);
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);
        $submission = MedicalHistory::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'discipline' => 'acupuncture',
            'discipline_responses' => [
                'tcm' => ['energy_level' => 'low'],
            ],
        ]);

        $this->actingAs($user);

        Livewire::test(ViewMedicalHistory::class, ['record' => $submission->id])
            ->assertSee('Five Element Acupuncture via Dr Five')
            ->assertSee('Five Element Acupuncture Intake')
            ->assertDontSee('TCM Assessment');
    }

    public function test_assigned_tcm_practitioner_overrides_five_element_practice_default_for_intake_label(): void
    {
        $practice = Practice::factory()->create([
            'practice_type' => PracticeType::FIVE_ELEMENT_ACUPUNCTURE,
            'discipline' => 'acupuncture',
        ]);
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $practitioner = Practitioner::factory()->create([
            'practice_id' => $practice->id,
            'clinical_style' => PracticeType::TCM_ACUPUNCTURE,
        ]);
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);
        $submission = MedicalHistory::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'discipline' => 'acupuncture',
            'discipline_responses' => [
                'tcm' => ['energy_level' => 'low'],
            ],
        ]);

        $this->actingAs($user);

        Livewire::test(ViewMedicalHistory::class, ['record' => $submission->id])
            ->assertSee('TCM Assessment')
            ->assertDontSee('Five Element Acupuncture Intake');
    }

    public function test_assigned_practitioner_with_null_clinical_style_falls_back_to_practice_default(): void
    {
        $practice = Practice::factory()->create([
            'practice_type' => PracticeType::FIVE_ELEMENT_ACUPUNCTURE,
            'discipline' => 'acupuncture',
        ]);
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $practitioner = Practitioner::factory()->create([
            'practice_id' => $practice->id,
            'clinical_style' => null,
        ]);
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);
        $submission = MedicalHistory::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'discipline' => 'acupuncture',
            'discipline_responses' => [
                'tcm' => ['energy_level' => 'low'],
            ],
        ]);

        $this->actingAs($user);

        Livewire::test(ViewMedicalHistory::class, ['record' => $submission->id])
            ->assertSee('Practice default — Five Element Acupuncture')
            ->assertSee('Five Element Acupuncture Intake')
            ->assertDontSee('TCM Assessment');
    }

    public function test_tcm_saved_intake_view_shows_tcm_compatible_label(): void
    {
        $practice = Practice::factory()->create([
            'practice_type' => PracticeType::TCM_ACUPUNCTURE,
            'discipline' => 'acupuncture',
        ]);
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);
        $submission = MedicalHistory::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'discipline' => 'acupuncture',
            'discipline_responses' => [
                'tcm' => ['energy_level' => 'low'],
            ],
        ]);

        $this->actingAs($user);

        Livewire::test(ViewMedicalHistory::class, ['record' => $submission->id])
            ->assertSee('TCM Assessment');
    }
}
