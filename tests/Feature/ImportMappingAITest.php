<?php

use App\Filament\Pages\Settings\ImportPatients;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\User;
use App\Services\AI\AIService;
use App\Services\AI\AIUnavailableException;
use App\Services\PracticeContext;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('AIService returns import mapping JSON from headers and supported fields only', function () {
    config([
        'services.ai.provider' => 'openai',
        'services.ai.openai.api_key' => 'test-key',
        'services.ai.openai.model' => 'gpt-test',
    ]);

    Http::fake([
        'api.openai.com/v1/responses' => Http::response([
            'output_text' => json_encode([
                'mappings' => [
                    ['header' => 'First', 'field' => 'first_name', 'confidence' => 'high'],
                    ['header' => 'Email Address', 'field' => 'email', 'confidence' => 'high'],
                ],
            ]),
        ]),
    ]);

    $result = app(AIService::class)->suggestImportMapping(
        ['First', 'Email Address'],
        ['first_name', 'last_name', 'email']
    );

    expect($result)->toBeArray()
        ->and($result['mappings'][0]['field'])->toBe('first_name');

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-key')
        && $request['model'] === 'gpt-test'
        && str_contains($request['instructions'], 'CSV import mapping assistant')
        && str_contains($request['instructions'], 'Only use supported fields')
        && str_contains($request['input'], 'csv_headers')
        && str_contains($request['input'], 'supported_patient_fields')
        && ! str_contains($request['input'], 'Alice Smith'));
});

it('creates import mapping suggestion and usage log without creating patients or mutating mappings', function () {
    $practice = Practice::factory()->create();
    $user = User::factory()->create(['practice_id' => $practice->id]);

    app()->instance(AIService::class, new class extends AIService {
        public function suggestImportMapping(array $headers, array $supportedFields): array|string
        {
            return [
                'mappings' => [
                    ['header' => 'First', 'field' => 'first_name', 'confidence' => 'high'],
                    ['header' => 'Surname', 'field' => 'last_name', 'confidence' => 'high'],
                ],
            ];
        }
    });

    $this->actingAs($user);

    Livewire::test(ImportPatients::class)
        ->set('step', 'map')
        ->set('detectedHeaders', ['First', 'Surname', 'Email Address'])
        ->set('mappings', [0 => '', 1 => '', 2 => ''])
        ->call('suggestColumnMapping')
        ->assertSet('mappings', [0 => '', 1 => '', 2 => '']);

    expect(Patient::withoutPracticeScope()->where('practice_id', $practice->id)->count())->toBe(0);

    $this->assertDatabaseHas('ai_suggestions', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'feature' => 'import_mapping',
        'status' => 'pending',
    ]);

    $this->assertDatabaseHas('ai_usage_logs', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'feature' => 'import_mapping',
        'status' => 'success',
    ]);

    $suggestion = \App\Models\AISuggestion::withoutPracticeScope()
        ->where('practice_id', $practice->id)
        ->where('feature', 'import_mapping')
        ->firstOrFail();

    expect($suggestion->original_text)->toContain('First')
        ->and($suggestion->original_text)->toContain('supported_fields')
        ->and($suggestion->original_text)->not->toContain('Alice Smith')
        ->and($suggestion->suggested_text)->toContain('first_name');
});

it('uses selected practice context for import mapping suggestions by super admin', function () {
    $practice = Practice::factory()->create();
    $otherPractice = Practice::factory()->create();
    $superAdmin = User::factory()->create(['practice_id' => null]);

    app()->instance(AIService::class, new class extends AIService {
        public function suggestImportMapping(array $headers, array $supportedFields): array|string
        {
            return ['mappings' => [['header' => 'Phone Number', 'field' => 'phone', 'confidence' => 'high']]];
        }
    });

    $this->actingAs($superAdmin);
    PracticeContext::setCurrentPracticeId($practice->id);

    Livewire::test(ImportPatients::class)
        ->set('step', 'map')
        ->set('detectedHeaders', ['Phone Number'])
        ->call('suggestColumnMapping');

    $this->assertDatabaseHas('ai_suggestions', [
        'practice_id' => $practice->id,
        'user_id' => $superAdmin->id,
        'feature' => 'import_mapping',
    ]);

    $this->assertDatabaseMissing('ai_suggestions', [
        'practice_id' => $otherPractice->id,
        'user_id' => $superAdmin->id,
        'feature' => 'import_mapping',
    ]);
});

it('logs failed import mapping suggestions cleanly', function () {
    $practice = Practice::factory()->create();
    $user = User::factory()->create(['practice_id' => $practice->id]);

    app()->instance(AIService::class, new class extends AIService {
        public function suggestImportMapping(array $headers, array $supportedFields): array|string
        {
            throw new AIUnavailableException('Import mapping AI offline');
        }
    });

    $this->actingAs($user);

    Livewire::test(ImportPatients::class)
        ->set('step', 'map')
        ->set('detectedHeaders', ['First', 'Last'])
        ->call('suggestColumnMapping');

    expect(Patient::withoutPracticeScope()->where('practice_id', $practice->id)->count())->toBe(0);

    $this->assertDatabaseHas('ai_suggestions', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'feature' => 'import_mapping',
        'suggested_text' => null,
        'status' => 'failed',
    ]);

    $this->assertDatabaseHas('ai_usage_logs', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'feature' => 'import_mapping',
        'status' => 'failed',
        'error_message' => 'Import mapping AI offline',
    ]);
});
