<?php

namespace Tests\Feature;

use App\Filament\Pages\CommunicationsDashboard;
use App\Mail\InviteBackMail;
use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\AppointmentType;
use App\Models\Encounter;
use App\Models\MessageLog;
use App\Models\Patient;
use App\Models\PatientCommunication;
use App\Models\PatientCommunicationPreference;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\States\Appointment\NoShow;
use App\Models\States\Appointment\Scheduled;
use App\Models\User;
use App\Services\AI\AIService;
use App\Services\AI\AIUnavailableException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class FollowUpCenterTest extends TestCase
{
    use RefreshDatabase;

    private Practice $practice;
    private User $user;
    private Practitioner $practitioner;
    private AppointmentType $appointmentType;
    private Carbon $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = Carbon::parse('2026-04-30 10:00:00');
        Carbon::setTestNow($this->now);

        $this->practice = Practice::factory()->create();
        $this->user = User::factory()->create(['practice_id' => $this->practice->id]);
        $this->practitioner = Practitioner::factory()->create(['practice_id' => $this->practice->id]);
        $this->appointmentType = AppointmentType::factory()->create(['practice_id' => $this->practice->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_follow_up_page_renders_patient_attention_list(): void
    {
        $needsFollowUp = $this->patient('Nora', 'Needs', 'es');
        $cooling = $this->patient('Cora', 'Cooling', 'fr');
        $atRisk = $this->patient('Risa', 'Risk', 'vi');
        $active = $this->patient('Ava', 'Active', 'de');

        $this->completedEncounter($needsFollowUp, $this->now->copy()->subDays(35));
        $this->completedEncounter($cooling, $this->now->copy()->subDays(60));
        $this->noShowAppointment($atRisk, $this->now->copy()->subDays(2));
        $this->completedEncounter($active, $this->now->copy()->subDays(60));
        $this->futureAppointment($active, $this->now->copy()->addDays(4));

        $this->actingAs($this->user);
        $followUpNames = Livewire::test(CommunicationsDashboard::class)
            ->instance()
            ->getFollowUpPatients()
            ->pluck('name')
            ->all();

        $this->assertNotContains('Ava Active', $followUpNames);

        $this
            ->get('/admin/communications-dashboard')
            ->assertSuccessful()
            ->assertSee('Follow-Up')
            ->assertSee('Needs Follow-Up')
            ->assertSee('Cooling')
            ->assertSee('At Risk')
            ->assertSee('Inactive')
            ->assertSee('These patients may benefit from a gentle check-in or invitation to return.')
            ->assertSee('Nora Needs')
            ->assertSee('Needs Follow-Up')
            ->assertSee('Spanish')
            ->assertSee('Cora Cooling')
            ->assertSee('Cooling')
            ->assertSee('French')
            ->assertSee('Risa Risk')
            ->assertSee('At Risk')
            ->assertSee('Vietnamese');
    }

    public function test_follow_up_status_filter_limits_displayed_patients(): void
    {
        $needsFollowUp = $this->patient('Nora', 'Needs', 'es');
        $cooling = $this->patient('Cora', 'Cooling', 'fr');
        $atRisk = $this->patient('Risa', 'Risk', 'vi');
        $inactive = $this->patient('Ina', 'Inactive', 'de');
        $active = $this->patient('Ava', 'Active', 'en');

        $this->completedEncounter($needsFollowUp, $this->now->copy()->subDays(35));
        $this->completedEncounter($cooling, $this->now->copy()->subDays(60));
        $this->noShowAppointment($atRisk, $this->now->copy()->subDays(2));
        $this->completedEncounter($inactive, $this->now->copy()->subDays(120));
        $this->completedEncounter($active, $this->now->copy()->subDays(10));

        $this->actingAs($this->user);

        $component = Livewire::test(CommunicationsDashboard::class)
            ->set('followUpStatusFilter', 'cooling');

        $this->assertSame(
            ['Cora Cooling'],
            $component->instance()->getFollowUpPatients()->pluck('name')->all()
        );

        $component->set('followUpStatusFilter', 'at_risk');

        $this->assertSame(
            ['Risa Risk'],
            $component->instance()->getFollowUpPatients()->pluck('name')->all()
        );

        $component->set('followUpStatusFilter', 'all');

        $names = $component->instance()->getFollowUpPatients()->pluck('name')->all();

        $this->assertContains('Nora Needs', $names);
        $this->assertContains('Cora Cooling', $names);
        $this->assertContains('Risa Risk', $names);
        $this->assertContains('Ina Inactive', $names);
        $this->assertNotContains('Ava Active', $names);
    }

    public function test_invite_back_preview_modal_renders_static_draft_without_sending(): void
    {
        $patient = $this->patient('Nora', 'Preview', 'es');
        $fallbackPatient = $this->patient('Cora', 'Fallback', 'fr');
        $this->completedEncounter($patient, $this->now->copy()->subDays(35));
        $this->completedEncounter($fallbackPatient, $this->now->copy()->subDays(60));

        $this->actingAs($this->user);

        Livewire::test(CommunicationsDashboard::class)
            ->assertSee('Nora Preview')
            ->call('openInviteBackPreview', $patient->id)
            ->assertSet('inviteBackPatientId', $patient->id)
            ->assertSee('Invite Back')
            ->assertSee('Preferred Language: Spanish')
            ->assertDontSee('Translate for Patient')
            ->assertSee('Subject')
            ->assertSee('Checking in')
            ->assertSee('Message body')
            ->assertSee('Hola Nora,')
            ->assertSee('Con aprecio,')
            ->assertSee('Review this message before sending.')
            ->assertSee('Preview message')
            ->assertSee('Saving a draft does not contact the patient.')
            ->assertSee('Sending will email the patient at')
            ->assertSee('Send Email')
            ->assertSee('Preview Only')
            ->call('closeInviteBackPreview')
            ->assertSet('inviteBackPatientId', null)
            ->call('openInviteBackPreview', $fallbackPatient->id)
            ->assertSee('Preferred Language: French')
            ->assertSee('A translated draft is not available yet, so this preview is shown in English.')
            ->assertSee('Translate for Patient')
            ->assertSee("Hi Cora,")
            ->assertSee('Warmly,');
    }

    public function test_invite_back_save_draft_records_patient_communication_without_sending(): void
    {
        Mail::fake();

        $patient = $this->patient('Nora', 'Draft', 'es');
        $this->completedEncounter($patient, $this->now->copy()->subDays(35));

        $this->actingAs($this->user);

        Livewire::test(CommunicationsDashboard::class)
            ->call('openInviteBackPreview', $patient->id)
            ->call('saveInviteBackDraft');

        $this->assertDatabaseHas('patient_communications', [
            'practice_id' => $this->practice->id,
            'patient_id' => $patient->id,
            'type' => PatientCommunication::TYPE_INVITE_BACK,
            'channel' => PatientCommunication::CHANNEL_PREVIEW,
            'language' => 'es',
            'subject' => 'Checking in',
            'status' => PatientCommunication::STATUS_DRAFT,
            'created_by' => $this->user->id,
        ]);

        $communication = PatientCommunication::withoutPracticeScope()
            ->where('patient_id', $patient->id)
            ->firstOrFail();

        $this->assertStringContainsString('Hola Nora,', $communication->body);
        Mail::assertNothingSent();
    }

    public function test_invite_back_send_email_requires_recipient_and_opt_in(): void
    {
        $missingEmail = $this->patient('Mia', 'MissingEmail', 'en');
        $missingEmail->update(['email' => null]);
        $optedOut = $this->patient('Owen', 'OptedOut', 'en');

        PatientCommunicationPreference::withoutPracticeScope()->create([
            'practice_id' => $this->practice->id,
            'patient_id' => $optedOut->id,
            'email_opt_in' => false,
            'sms_opt_in' => false,
            'opted_out_at' => $this->now,
        ]);

        $this->completedEncounter($missingEmail, $this->now->copy()->subDays(35));
        $this->completedEncounter($optedOut, $this->now->copy()->subDays(35));

        $this->actingAs($this->user);

        Livewire::test(CommunicationsDashboard::class)
            ->call('openInviteBackPreview', $missingEmail->id)
            ->assertSee('Add an email address before sending this follow-up.')
            ->assertDontSee('Send Email')
            ->call('sendInviteBackEmail')
            ->call('openInviteBackPreview', $optedOut->id)
            ->assertSee('This patient has opted out of messages.')
            ->assertDontSee('Send Email')
            ->call('sendInviteBackEmail');

        $this->assertDatabaseMissing('patient_communications', [
            'patient_id' => $missingEmail->id,
            'channel' => PatientCommunication::CHANNEL_EMAIL,
        ]);
        $this->assertDatabaseMissing('patient_communications', [
            'patient_id' => $optedOut->id,
            'channel' => PatientCommunication::CHANNEL_EMAIL,
        ]);
    }

    public function test_invite_back_send_email_records_sent_communication_and_message_log(): void
    {
        Mail::fake();

        $patient = $this->patient('Nora', 'SendEmail', 'es');
        $patient->update(['email' => 'nora.send@example.test']);
        $this->completedEncounter($patient, $this->now->copy()->subDays(35));

        $this->actingAs($this->user);

        Livewire::test(CommunicationsDashboard::class)
            ->call('openInviteBackPreview', $patient->id)
            ->assertSee('Sending will email the patient at nora.send@example.test.')
            ->assertSee('Send Email will contact the patient now.')
            ->assertSee('Send Email')
            ->call('sendInviteBackEmail');

        Mail::assertSent(InviteBackMail::class, 1);
        Mail::assertSent(InviteBackMail::class, function (InviteBackMail $mail): bool {
            return $mail->requestUrl !== null
                && str_contains($mail->requestUrl, '/appointment-request/');
        });

        $communication = PatientCommunication::withoutPracticeScope()
            ->where('patient_id', $patient->id)
            ->where('channel', PatientCommunication::CHANNEL_EMAIL)
            ->firstOrFail();

        $this->assertSame(PatientCommunication::STATUS_SENT, $communication->status);
        $this->assertSame('es', $communication->language);
        $this->assertSame('Checking in', $communication->subject);
        $this->assertStringContainsString('Hola Nora,', $communication->body);
        $this->assertNotNull($communication->sent_at);

        $messageLog = MessageLog::withoutPracticeScope()
            ->where('patient_id', $patient->id)
            ->where('channel', PatientCommunication::CHANNEL_EMAIL)
            ->firstOrFail();

        $this->assertSame('sent', $messageLog->status);
        $this->assertSame('nora.send@example.test', $messageLog->recipient);
        $this->assertNotNull($messageLog->sent_at);

        $appointmentRequest = AppointmentRequest::withoutPracticeScope()
            ->where('patient_id', $patient->id)
            ->where('patient_communication_id', $communication->id)
            ->firstOrFail();

        $this->assertSame(AppointmentRequest::STATUS_LINK_SENT, $appointmentRequest->status);
        $this->assertSame($this->practice->id, $appointmentRequest->practice_id);
        $this->assertNotNull($appointmentRequest->token_hash);
    }

    public function test_invite_back_send_email_can_skip_appointment_request_link(): void
    {
        Mail::fake();

        $patient = $this->patient('Nora', 'NoRequestLink', 'en');
        $patient->update(['email' => 'nora.nolink@example.test']);
        $this->completedEncounter($patient, $this->now->copy()->subDays(35));

        $this->actingAs($this->user);

        Livewire::test(CommunicationsDashboard::class)
            ->call('openInviteBackPreview', $patient->id)
            ->assertSee('Include appointment request link')
            ->set('includeInviteBackRequestLink', false)
            ->call('sendInviteBackEmail');

        Mail::assertSent(InviteBackMail::class, function (InviteBackMail $mail): bool {
            return $mail->requestUrl === null;
        });

        $this->assertDatabaseMissing('appointment_requests', [
            'patient_id' => $patient->id,
        ]);
    }

    public function test_invite_back_send_email_failure_keeps_draft_visible_and_logs_failure(): void
    {
        $patient = $this->patient('Cora', 'SendFailure', 'fr');
        $patient->update(['email' => 'cora.failure@example.test']);
        $this->completedEncounter($patient, $this->now->copy()->subDays(60));

        Mail::shouldReceive('to')->once()->with('cora.failure@example.test')->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('SMTP error'));

        $this->actingAs($this->user);

        Livewire::test(CommunicationsDashboard::class)
            ->call('openInviteBackPreview', $patient->id)
            ->assertSee('Hi Cora,')
            ->call('sendInviteBackEmail')
            ->assertSee('Hi Cora,');

        $communication = PatientCommunication::withoutPracticeScope()
            ->where('patient_id', $patient->id)
            ->where('channel', PatientCommunication::CHANNEL_EMAIL)
            ->firstOrFail();

        $this->assertSame(PatientCommunication::STATUS_FAILED, $communication->status);
        $this->assertStringContainsString('Hi Cora,', $communication->body);

        $messageLog = MessageLog::withoutPracticeScope()
            ->where('patient_id', $patient->id)
            ->where('channel', PatientCommunication::CHANNEL_EMAIL)
            ->firstOrFail();

        $this->assertSame('failed', $messageLog->status);
        $this->assertNotNull($messageLog->failed_at);
        $this->assertStringContainsString('SMTP error', $messageLog->failure_reason);

        $appointmentRequest = AppointmentRequest::withoutPracticeScope()
            ->where('patient_id', $patient->id)
            ->firstOrFail();

        $this->assertSame(AppointmentRequest::STATUS_FAILED, $appointmentRequest->status);
    }

    public function test_invite_back_translation_preview_updates_modal_state_without_sending(): void
    {
        Mail::fake();

        $patient = $this->patient('Cora', 'Translate', 'fr');
        $this->completedEncounter($patient, $this->now->copy()->subDays(60));

        app()->instance(AIService::class, new class extends AIService {
            public function translateText(string $text, string $targetLanguage, array $context = []): string
            {
                return 'Bonjour Cora, nous voulions simplement prendre de vos nouvelles.';
            }
        });

        $this->actingAs($this->user);

        Livewire::test(CommunicationsDashboard::class)
            ->call('openInviteBackPreview', $patient->id)
            ->assertSee('Translate for Patient')
            ->call('translateInviteBackDraft')
            ->assertSet('inviteBackTranslatedBody', 'Bonjour Cora, nous voulions simplement prendre de vos nouvelles.')
            ->assertSet('inviteBackTranslationError', null)
            ->assertSee('Translated preview')
            ->assertSee('Please review the translation before using it.');

        Mail::assertNothingSent();

        $this->assertDatabaseHas('ai_suggestions', [
            'practice_id' => $this->practice->id,
            'user_id' => $this->user->id,
            'patient_id' => $patient->id,
            'feature' => 'invite_back_translation',
            'suggested_text' => 'Bonjour Cora, nous voulions simplement prendre de vos nouvelles.',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('ai_usage_logs', [
            'practice_id' => $this->practice->id,
            'user_id' => $this->user->id,
            'feature' => 'invite_back_translation',
            'status' => 'success',
        ]);
    }

    public function test_invite_back_translation_failure_keeps_english_draft(): void
    {
        Mail::fake();

        $patient = $this->patient('Cora', 'Failure', 'de');
        $this->completedEncounter($patient, $this->now->copy()->subDays(60));

        app()->instance(AIService::class, new class extends AIService {
            public function translateText(string $text, string $targetLanguage, array $context = []): string
            {
                throw new AIUnavailableException('Invite back translation offline');
            }
        });

        $this->actingAs($this->user);

        Livewire::test(CommunicationsDashboard::class)
            ->call('openInviteBackPreview', $patient->id)
            ->assertSee('Hi Cora,')
            ->call('translateInviteBackDraft')
            ->assertSet('inviteBackTranslatedBody', null)
            ->assertSet('inviteBackTranslationError', 'Translation is unavailable right now. You can still use the English draft.')
            ->assertSee('Hi Cora,')
            ->assertSee('Translation is unavailable right now. You can still use the English draft.');

        Mail::assertNothingSent();

        $this->assertDatabaseHas('ai_suggestions', [
            'practice_id' => $this->practice->id,
            'user_id' => $this->user->id,
            'patient_id' => $patient->id,
            'feature' => 'invite_back_translation',
            'suggested_text' => null,
            'status' => 'failed',
        ]);

        $this->assertDatabaseHas('ai_usage_logs', [
            'practice_id' => $this->practice->id,
            'user_id' => $this->user->id,
            'feature' => 'invite_back_translation',
            'status' => 'failed',
            'error_message' => 'Invite back translation offline',
        ]);
    }

    public function test_invite_back_save_draft_after_translation_stores_translated_body(): void
    {
        Mail::fake();

        $patient = $this->patient('Cora', 'SaveTranslated', 'fr');
        $this->completedEncounter($patient, $this->now->copy()->subDays(60));

        app()->instance(AIService::class, new class extends AIService {
            public function translateText(string $text, string $targetLanguage, array $context = []): string
            {
                return 'Bonjour Cora, nous serions heureux de vous revoir.';
            }
        });

        $this->actingAs($this->user);

        Livewire::test(CommunicationsDashboard::class)
            ->call('openInviteBackPreview', $patient->id)
            ->call('translateInviteBackDraft')
            ->call('saveInviteBackDraft');

        $communication = PatientCommunication::withoutPracticeScope()
            ->where('patient_id', $patient->id)
            ->firstOrFail();

        $this->assertSame('Bonjour Cora, nous serions heureux de vous revoir.', $communication->body);
        $this->assertSame('fr', $communication->language);
        Mail::assertNothingSent();
    }

    public function test_english_and_spanish_invite_back_drafts_do_not_call_ai_translation(): void
    {
        $englishPatient = $this->patient('Erin', 'English', 'en');
        $spanishPatient = $this->patient('Nora', 'Spanish', 'es');
        $this->completedEncounter($englishPatient, $this->now->copy()->subDays(60));
        $this->completedEncounter($spanishPatient, $this->now->copy()->subDays(35));

        app()->instance(AIService::class, new class extends AIService {
            public function translateText(string $text, string $targetLanguage, array $context = []): string
            {
                throw new AIUnavailableException('AI should not be called for deterministic drafts.');
            }
        });

        $this->actingAs($this->user);

        Livewire::test(CommunicationsDashboard::class)
            ->call('openInviteBackPreview', $englishPatient->id)
            ->assertDontSee('Translate for Patient')
            ->call('translateInviteBackDraft')
            ->assertSet('inviteBackTranslatedBody', null)
            ->call('openInviteBackPreview', $spanishPatient->id)
            ->assertDontSee('Translate for Patient')
            ->call('translateInviteBackDraft')
            ->assertSet('inviteBackTranslatedBody', null);

        $this->assertDatabaseMissing('ai_usage_logs', [
            'practice_id' => $this->practice->id,
            'feature' => 'invite_back_translation',
        ]);
    }

    public function test_follow_up_page_is_practice_scoped(): void
    {
        $visiblePatient = $this->patient('Visible', 'Patient', 'en');
        $otherPractice = Practice::factory()->create();
        $hiddenPatient = Patient::factory()->create([
            'practice_id' => $otherPractice->id,
            'first_name' => 'Hidden',
            'last_name' => 'Patient',
            'name' => 'Hidden Patient',
        ]);

        $this->completedEncounter($visiblePatient, $this->now->copy()->subDays(35));

        Encounter::factory()->create([
            'practice_id' => $otherPractice->id,
            'patient_id' => $hiddenPatient->id,
            'appointment_id' => null,
            'practitioner_id' => Practitioner::factory()->create(['practice_id' => $otherPractice->id])->id,
            'status' => 'complete',
            'visit_date' => $this->now->copy()->subDays(35)->toDateString(),
            'completed_on' => $this->now->copy()->subDays(35),
        ]);

        $this->actingAs($this->user)
            ->get('/admin/communications-dashboard')
            ->assertSuccessful()
            ->assertSee('Visible Patient')
            ->assertDontSee('Hidden Patient');
    }

    public function test_patient_communication_records_are_practice_scoped(): void
    {
        $visiblePatient = $this->patient('Visible', 'Draft', 'en');
        $otherPractice = Practice::factory()->create();
        $hiddenPatient = Patient::factory()->create([
            'practice_id' => $otherPractice->id,
            'first_name' => 'Hidden',
            'last_name' => 'Draft',
        ]);

        PatientCommunication::withoutPracticeScope()->create([
            'practice_id' => $this->practice->id,
            'patient_id' => $visiblePatient->id,
            'type' => PatientCommunication::TYPE_INVITE_BACK,
            'channel' => PatientCommunication::CHANNEL_PREVIEW,
            'language' => 'en',
            'subject' => 'Checking in',
            'body' => 'Visible body',
            'status' => PatientCommunication::STATUS_DRAFT,
            'created_by' => $this->user->id,
        ]);

        PatientCommunication::withoutPracticeScope()->create([
            'practice_id' => $otherPractice->id,
            'patient_id' => $hiddenPatient->id,
            'type' => PatientCommunication::TYPE_INVITE_BACK,
            'channel' => PatientCommunication::CHANNEL_PREVIEW,
            'language' => 'en',
            'subject' => 'Checking in',
            'body' => 'Hidden body',
            'status' => PatientCommunication::STATUS_DRAFT,
        ]);

        $this->actingAs($this->user);

        $this->assertSame(1, PatientCommunication::query()->count());
        $this->assertSame($visiblePatient->id, PatientCommunication::query()->firstOrFail()->patient_id);
    }

    public function test_patient_detail_shows_recent_follow_up_communication(): void
    {
        $patient = $this->patient('Nora', 'History', 'en');

        PatientCommunication::withoutPracticeScope()->create([
            'practice_id' => $this->practice->id,
            'patient_id' => $patient->id,
            'type' => PatientCommunication::TYPE_INVITE_BACK,
            'channel' => PatientCommunication::CHANNEL_PREVIEW,
            'language' => 'en',
            'subject' => 'Checking in',
            'body' => 'Draft body',
            'status' => PatientCommunication::STATUS_DRAFT,
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->get("/admin/patients/{$patient->id}")
            ->assertSuccessful()
            ->assertSee('Recent Follow-Up')
            ->assertSee('Invite Back')
            ->assertSee('Draft')
            ->assertSee('EN')
            ->assertSee('Checking in');
    }

    private function patient(string $firstName, string $lastName, string $language): Patient
    {
        return Patient::factory()->create([
            'practice_id' => $this->practice->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => "{$firstName} {$lastName}",
            'preferred_language' => $language,
        ]);
    }

    private function completedEncounter(Patient $patient, Carbon $completedAt): Encounter
    {
        return Encounter::factory()->create([
            'practice_id' => $this->practice->id,
            'patient_id' => $patient->id,
            'appointment_id' => null,
            'practitioner_id' => $this->practitioner->id,
            'status' => 'complete',
            'visit_date' => $completedAt->toDateString(),
            'completed_on' => $completedAt,
        ]);
    }

    private function noShowAppointment(Patient $patient, Carbon $date): Appointment
    {
        return Appointment::factory()->create([
            'practice_id' => $this->practice->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $this->practitioner->id,
            'appointment_type_id' => $this->appointmentType->id,
            'status' => NoShow::$name,
            'start_datetime' => $date,
            'end_datetime' => $date->copy()->addHour(),
        ]);
    }

    private function futureAppointment(Patient $patient, Carbon $date): Appointment
    {
        return Appointment::factory()->create([
            'practice_id' => $this->practice->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $this->practitioner->id,
            'appointment_type_id' => $this->appointmentType->id,
            'status' => Scheduled::$name,
            'start_datetime' => $date,
            'end_datetime' => $date->copy()->addHour(),
        ]);
    }
}
