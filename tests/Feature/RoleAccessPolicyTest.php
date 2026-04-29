<?php

use App\Filament\Resources\Encounters\Pages\Concerns\HandlesEncounterAIActions;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Encounters\EncounterResource;
use App\Filament\Resources\MedicalHistories\Pages\ViewMedicalHistory;
use App\Filament\Resources\MedicalHistories\MedicalHistoryResource;
use App\Filament\Resources\Patients\PatientResource;
use App\Filament\Resources\Practitioners\PractitionerResource;
use App\Models\AIUsageLog;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Encounter;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\User;
use App\Services\AI\AIService;
use App\Support\PracticeAccessRoles;

function roleAccessFixtures(?Practice $practice = null): array
{
    $practice ??= Practice::factory()->create();
    $patient = Patient::factory()->create(['practice_id' => $practice->id]);
    $practitionerUser = User::factory()->create(['practice_id' => $practice->id]);
    $practitioner = Practitioner::factory()->create([
        'practice_id' => $practice->id,
        'user_id' => $practitionerUser->id,
    ]);
    $appointmentType = AppointmentType::factory()->create(['practice_id' => $practice->id]);
    $appointment = Appointment::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'practitioner_id' => $practitioner->id,
        'appointment_type_id' => $appointmentType->id,
    ]);
    $encounter = Encounter::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'practitioner_id' => $practitioner->id,
        'appointment_id' => $appointment->id,
        'visit_notes' => 'Patient reports neck tension.',
    ]);
    $intake = MedicalHistory::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'appointment_id' => $appointment->id,
        'status' => 'complete',
        'reason_for_visit' => 'Patient reports neck tension.',
    ]);

    return compact('practice', 'patient', 'practitionerUser', 'practitioner', 'appointmentType', 'appointment', 'encounter', 'intake');
}

function roleAccessEncounterAIHarness(Encounter $encounter, array $data): object
{
    return new class($encounter, $data)
    {
        use HandlesEncounterAIActions;

        public array $data;

        public object $form;

        public function __construct(public Encounter $record, array $data)
        {
            $this->data = $data;
            $this->form = new class
            {
                public function fillPartially(
                    array $state,
                    array $paths,
                    bool $shouldCallHydrationHooks = false,
                    bool $shouldFillStateWithNull = false,
                ): void {
                    //
                }
            };
        }
    };
}

beforeEach(function (): void {
    PracticeAccessRoles::ensureRoles();
});

it('backfills first user per practice as owner and practitioner-linked users as practitioners', function () {
    $practice = Practice::factory()->create();
    $firstUser = User::factory()->create(['practice_id' => $practice->id]);
    $practitionerUser = User::factory()->create(['practice_id' => $practice->id]);
    Practitioner::factory()->create([
        'practice_id' => $practice->id,
        'user_id' => $practitionerUser->id,
    ]);
    $adminUser = User::factory()->create(['practice_id' => $practice->id]);
    $superAdmin = User::factory()->create(['practice_id' => null]);

    PracticeAccessRoles::backfillExistingUsers();

    expect($firstUser->fresh()->isOwner())->toBeTrue()
        ->and($practitionerUser->fresh()->isPractitioner())->toBeTrue()
        ->and($adminUser->fresh()->isAdministrator())->toBeTrue()
        ->and($superAdmin->fresh()->isOwner())->toBeTrue();
});

it('repairs practices whose only user already has a practitioner role', function () {
    $practice = Practice::factory()->create();
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $user->assignRole(User::ROLE_PRACTITIONER);
    Practitioner::factory()->create([
        'practice_id' => $practice->id,
        'user_id' => $user->id,
    ]);

    PracticeAccessRoles::backfillExistingUsers();

    $user = $user->fresh();

    expect($user->isPractitioner())->toBeTrue()
        ->and($user->isOwner())->toBeTrue();
});

it('repairs practices whose only user already has an administrator role', function () {
    $practice = Practice::factory()->create();
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $user->assignRole(User::ROLE_ADMINISTRATOR);

    PracticeAccessRoles::backfillExistingUsers();

    $user = $user->fresh();

    expect($user->isAdministrator())->toBeTrue()
        ->and($user->isOwner())->toBeTrue();
});

it('leaves existing practice owners in place while preserving other role assignments', function () {
    $practice = Practice::factory()->create();
    $owner = User::factory()->create(['practice_id' => $practice->id]);
    $owner->assignRole(User::ROLE_OWNER);
    $administrator = User::factory()->create(['practice_id' => $practice->id]);
    $administrator->assignRole(User::ROLE_ADMINISTRATOR);

    PracticeAccessRoles::backfillExistingUsers();

    expect($owner->fresh()->isOwner())->toBeTrue()
        ->and($administrator->fresh()->isAdministrator())->toBeTrue()
        ->and($administrator->fresh()->isOwner())->toBeFalse();
});

it('allows owner access to all records in their practice', function () {
    $fixtures = roleAccessFixtures();
    $owner = User::factory()->create(['practice_id' => $fixtures['practice']->id]);
    $owner->assignRole(User::ROLE_OWNER);

    expect($owner->can('view', $fixtures['patient']))->toBeTrue()
        ->and($owner->can('update', $fixtures['appointment']))->toBeTrue()
        ->and($owner->can('update', $fixtures['encounter']))->toBeTrue()
        ->and($owner->can('view', $fixtures['intake']))->toBeTrue();
});

it('allows administrators to manage operational records but not owner roles', function () {
    $fixtures = roleAccessFixtures();
    $administrator = User::factory()->create(['practice_id' => $fixtures['practice']->id]);
    $administrator->assignRole(User::ROLE_ADMINISTRATOR);
    $owner = User::factory()->create(['practice_id' => $fixtures['practice']->id]);
    $owner->assignRole(User::ROLE_OWNER);

    expect($administrator->can('update', $fixtures['patient']))->toBeTrue()
        ->and($administrator->can('update', $fixtures['appointment']))->toBeTrue()
        ->and($administrator->can('update', $fixtures['intake']))->toBeTrue()
        ->and($administrator->can('manageOwnerRole', $owner))->toBeFalse();
});

it('allows practitioners to access assigned encounters only', function () {
    $fixtures = roleAccessFixtures();
    $fixtures['practitionerUser']->assignRole(User::ROLE_PRACTITIONER);

    $other = roleAccessFixtures($fixtures['practice']);
    $other['practitionerUser']->assignRole(User::ROLE_PRACTITIONER);

    expect($fixtures['practitionerUser']->can('view', $fixtures['encounter']))->toBeTrue()
        ->and($fixtures['practitionerUser']->can('update', $fixtures['encounter']))->toBeTrue()
        ->and($fixtures['practitionerUser']->can('view', $other['encounter']))->toBeFalse()
        ->and($fixtures['practitionerUser']->can('update', $other['encounter']))->toBeFalse();
});

it('allows practitioners to view directly assigned medical histories only', function () {
    $fixtures = roleAccessFixtures();
    $fixtures['practitionerUser']->assignRole(User::ROLE_PRACTITIONER);

    $assigned = MedicalHistory::factory()->create([
        'practice_id' => $fixtures['practice']->id,
        'patient_id' => $fixtures['patient']->id,
        'practitioner_id' => $fixtures['practitioner']->id,
    ]);

    $other = roleAccessFixtures($fixtures['practice']);
    $unrelated = MedicalHistory::factory()->create([
        'practice_id' => $fixtures['practice']->id,
        'patient_id' => $other['patient']->id,
        'practitioner_id' => $other['practitioner']->id,
    ]);

    expect($fixtures['practitionerUser']->can('view', $assigned))->toBeTrue()
        ->and($fixtures['practitionerUser']->can('view', $unrelated))->toBeFalse()
        ->and($fixtures['practitionerUser']->can('update', $assigned))->toBeFalse();
});

it('allows owners and administrators to view all practice medical histories', function () {
    $fixtures = roleAccessFixtures();
    $owner = User::factory()->create(['practice_id' => $fixtures['practice']->id]);
    $owner->assignRole(User::ROLE_OWNER);
    $administrator = User::factory()->create(['practice_id' => $fixtures['practice']->id]);
    $administrator->assignRole(User::ROLE_ADMINISTRATOR);
    $assigned = MedicalHistory::factory()->create([
        'practice_id' => $fixtures['practice']->id,
        'patient_id' => $fixtures['patient']->id,
        'practitioner_id' => $fixtures['practitioner']->id,
    ]);

    expect($owner->can('view', $assigned))->toBeTrue()
        ->and($owner->can('update', $assigned))->toBeTrue()
        ->and($administrator->can('view', $assigned))->toBeTrue()
        ->and($administrator->can('update', $assigned))->toBeTrue();
});

it('filters Filament clinical resource lists to assigned records for practitioners', function () {
    $fixtures = roleAccessFixtures();
    $fixtures['practitionerUser']->assignRole(User::ROLE_PRACTITIONER);
    $other = roleAccessFixtures($fixtures['practice']);
    $directAssigned = MedicalHistory::factory()->create([
        'practice_id' => $fixtures['practice']->id,
        'patient_id' => $fixtures['patient']->id,
        'practitioner_id' => $fixtures['practitioner']->id,
    ]);

    $this->actingAs($fixtures['practitionerUser']);

    expect(AppointmentResource::getEloquentQuery()->pluck('id')->all())->toBe([$fixtures['appointment']->id])
        ->and(EncounterResource::getEloquentQuery()->pluck('id')->all())->toBe([$fixtures['encounter']->id])
        ->and(MedicalHistoryResource::getEloquentQuery()->pluck('id')->sort()->values()->all())->toBe(collect([$fixtures['intake']->id, $directAssigned->id])->sort()->values()->all())
        ->and(PatientResource::getEloquentQuery()->pluck('id')->all())->toBe([$fixtures['patient']->id])
        ->and(PractitionerResource::getEloquentQuery()->pluck('id')->all())->toBe([$fixtures['practitioner']->id])
        ->and($fixtures['practitionerUser']->can('view', $other['encounter']))->toBeFalse();
});

it('blocks cross-practice access for practice users', function () {
    $fixtures = roleAccessFixtures();
    $other = roleAccessFixtures();
    $administrator = User::factory()->create(['practice_id' => $fixtures['practice']->id]);
    $administrator->assignRole(User::ROLE_ADMINISTRATOR);

    expect($administrator->can('view', $other['patient']))->toBeFalse()
        ->and($administrator->can('update', $other['appointment']))->toBeFalse()
        ->and($administrator->can('update', $other['encounter']))->toBeFalse()
        ->and($administrator->can('view', $other['intake']))->toBeFalse();
});

it('allows visit note AI only for authorized encounters', function () {
    $fixtures = roleAccessFixtures();
    $fixtures['practitionerUser']->assignRole(User::ROLE_PRACTITIONER);

    app()->instance(AIService::class, new class extends AIService
    {
        public function improveNote(string $note, array $context = []): string
        {
            return 'Authorized AI note.';
        }
    });

    $this->actingAs($fixtures['practitionerUser']);

    $authorized = roleAccessEncounterAIHarness($fixtures['encounter'], [
        'visit_note_document' => 'Patient reports neck tension.',
    ]);
    $authorized->improveNote(app(AIService::class));

    expect($authorized->data['ai_suggestion'])->toBe('Authorized AI note.');

    $other = roleAccessFixtures();
    $unauthorized = roleAccessEncounterAIHarness($other['encounter'], [
        'visit_note_document' => 'Another practitioner note.',
    ]);
    $unauthorized->improveNote(app(AIService::class));

    expect($unauthorized->data)->not->toHaveKey('ai_suggestion')
        ->and(AIUsageLog::count())->toBe(1);
});

it('allows intake summary AI only for authorized intakes', function () {
    $fixtures = roleAccessFixtures();
    $fixtures['practitionerUser']->assignRole(User::ROLE_PRACTITIONER);

    app()->instance(AIService::class, new class extends AIService
    {
        public function summarizeIntake(array $context): string
        {
            return 'Authorized intake summary.';
        }
    });

    $this->actingAs($fixtures['practitionerUser']);

    $authorized = new ViewMedicalHistory;
    $authorized->record = $fixtures['intake'];
    $authorized->generateIntakeSummary(app(AIService::class));

    expect($authorized->aiIntakeSummary)->toBe('Authorized intake summary.');

    $other = roleAccessFixtures();
    $unauthorized = new ViewMedicalHistory;
    $unauthorized->record = $other['intake'];
    $unauthorized->generateIntakeSummary(app(AIService::class));

    expect($unauthorized->aiIntakeSummary)->toBeNull()
        ->and(AIUsageLog::count())->toBe(1);
});

it('keeps current demo-style admin users with no assigned role from being locked out', function () {
    $fixtures = roleAccessFixtures();
    $legacyAdmin = User::factory()->create(['practice_id' => $fixtures['practice']->id]);

    expect($legacyAdmin->roles)->toHaveCount(0)
        ->and($legacyAdmin->isAdministrator())->toBeTrue()
        ->and($legacyAdmin->can('update', $fixtures['patient']))->toBeTrue()
        ->and($legacyAdmin->can('update', $fixtures['encounter']))->toBeTrue();
});
