<?php

namespace App\Filament\Resources\Encounters\Pages\Concerns;

use App\Models\AISuggestion;
use App\Models\AIUsageLog;
use App\Models\Encounter;
use App\Models\Practice;
use App\Services\AI\AIService;
use App\Services\PracticeContext;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

trait HandlesEncounterAIActions
{
    private const AI_IMPROVABLE_FIELDS = [
        'visit_notes' => 'Encounter note',
        'subjective' => 'Subjective',
        'objective' => 'Objective',
        'assessment' => 'Assessment',
        'plan' => 'Plan',
    ];

    public function improveNote(AIService $ai): void
    {
        $practiceId = PracticeContext::currentPracticeId();
        $note = trim((string) data_get($this->data, 'visit_notes', ''));

        if (! $practiceId) {
            Notification::make()
                ->title('Select a practice before using AI.')
                ->danger()
                ->send();

            return;
        }

        $suggestion = $this->createAISuggestion($practiceId, $note, 'pending', 'improve_note');

        try {
            $suggestedText = $ai->improveNote($note, [
                'discipline' => $this->encounterAIValue('discipline'),
                'chief_complaint' => data_get($this->data, 'chief_complaint'),
            ]);

            $suggestion->update([
                'suggested_text' => $suggestedText,
                'status' => 'pending',
            ]);

            AIUsageLog::create([
                'practice_id' => $practiceId,
                'user_id' => auth()->id(),
                'feature' => 'improve_note',
                'status' => 'success',
            ]);

            $this->updateEncounterAIFormState([
                'ai_suggestion' => $suggestedText,
                'ai_suggestion_id' => $suggestion->id,
            ]);

            Log::info('Encounter AI suggestion state updated.', [
                'feature' => 'improve_note',
                'practice_id' => $practiceId,
                'user_id' => auth()->id(),
                'suggestion_id' => $suggestion->id,
                'suggestion_length' => strlen($suggestedText),
                'has_ai_suggestion_state' => data_get($this->data, 'ai_suggestion') !== null,
            ]);

            Notification::make()
                ->title('AI suggestion ready.')
                ->success()
                ->send();
        } catch (Throwable $exception) {
            $suggestion->update([
                'status' => 'failed',
            ]);

            AIUsageLog::create([
                'practice_id' => $practiceId,
                'user_id' => auth()->id(),
                'feature' => 'improve_note',
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            Notification::make()
                ->title('Improve Note is unavailable.')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function acceptAISuggestion(): void
    {
        $suggestedText = trim((string) data_get($this->data, 'ai_suggestion', ''));

        if ($suggestedText === '') {
            Notification::make()
                ->title('Generate an AI suggestion before accepting.')
                ->danger()
                ->send();

            return;
        }

        $this->updateEncounterAIFormState([
            'visit_notes' => $suggestedText,
        ]);

        $record = $this->encounterAIRecord();
        if ($record?->exists) {
            $record->update(['visit_notes' => $suggestedText]);
        }

        $suggestionId = data_get($this->data, 'ai_suggestion_id');
        if ($suggestionId) {
            AISuggestion::whereKey($suggestionId)->update([
                'accepted_text' => $suggestedText,
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);
        }

        Notification::make()
            ->title('AI suggestion accepted.')
            ->success()
            ->send();
    }

    public function checkMissingDocumentation(AIService $ai): void
    {
        $practiceId = PracticeContext::currentPracticeId();
        $note = trim((string) data_get($this->data, 'visit_notes', ''));

        if (! $practiceId) {
            Notification::make()
                ->title('Select a practice before using AI.')
                ->danger()
                ->send();

            return;
        }

        if (! $this->insuranceBillingEnabledForAI()) {
            Notification::make()
                ->title('Documentation check requires insurance billing to be enabled.')
                ->danger()
                ->send();

            return;
        }

        $suggestion = $this->createAISuggestion($practiceId, $note, 'pending', 'documentation_check');

        try {
            $checklist = $ai->checkMissingDocumentation($note, [
                'discipline' => $this->encounterAIValue('discipline'),
                'chief_complaint' => data_get($this->data, 'chief_complaint'),
            ]);

            $suggestion->update([
                'suggested_text' => $checklist,
                'status' => 'pending',
            ]);

            AIUsageLog::create([
                'practice_id' => $practiceId,
                'user_id' => auth()->id(),
                'feature' => 'documentation_check',
                'status' => 'success',
            ]);

            $this->updateEncounterAIFormState([
                'documentation_check_result' => $checklist,
            ]);

            Log::info('Encounter AI suggestion state updated.', [
                'feature' => 'documentation_check',
                'practice_id' => $practiceId,
                'user_id' => auth()->id(),
                'suggestion_id' => $suggestion->id,
                'suggestion_length' => strlen($checklist),
                'has_documentation_check_state' => data_get($this->data, 'documentation_check_result') !== null,
            ]);

            Notification::make()
                ->title('Documentation check ready.')
                ->success()
                ->send();
        } catch (Throwable $exception) {
            $suggestion->update([
                'status' => 'failed',
            ]);

            AIUsageLog::create([
                'practice_id' => $practiceId,
                'user_id' => auth()->id(),
                'feature' => 'documentation_check',
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            Notification::make()
                ->title('Documentation check is unavailable.')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function improveVisitNotesField(AIService $ai): void
    {
        $this->improveField($ai, 'visit_notes');
    }

    public function improveSubjectiveField(AIService $ai): void
    {
        $this->improveField($ai, 'subjective');
    }

    public function improveObjectiveField(AIService $ai): void
    {
        $this->improveField($ai, 'objective');
    }

    public function improveAssessmentField(AIService $ai): void
    {
        $this->improveField($ai, 'assessment');
    }

    public function improvePlanField(AIService $ai): void
    {
        $this->improveField($ai, 'plan');
    }

    public function acceptVisitNotesFieldSuggestion(): void
    {
        $this->acceptFieldSuggestion('visit_notes');
    }

    public function acceptSubjectiveFieldSuggestion(): void
    {
        $this->acceptFieldSuggestion('subjective');
    }

    public function acceptObjectiveFieldSuggestion(): void
    {
        $this->acceptFieldSuggestion('objective');
    }

    public function acceptAssessmentFieldSuggestion(): void
    {
        $this->acceptFieldSuggestion('assessment');
    }

    public function acceptPlanFieldSuggestion(): void
    {
        $this->acceptFieldSuggestion('plan');
    }

    public function dismissVisitNotesFieldSuggestion(): void
    {
        $this->dismissFieldSuggestion('visit_notes');
    }

    public function dismissSubjectiveFieldSuggestion(): void
    {
        $this->dismissFieldSuggestion('subjective');
    }

    public function dismissObjectiveFieldSuggestion(): void
    {
        $this->dismissFieldSuggestion('objective');
    }

    public function dismissAssessmentFieldSuggestion(): void
    {
        $this->dismissFieldSuggestion('assessment');
    }

    public function dismissPlanFieldSuggestion(): void
    {
        $this->dismissFieldSuggestion('plan');
    }

    public function improveField(AIService $ai, string $field): void
    {
        if (! array_key_exists($field, self::AI_IMPROVABLE_FIELDS)) {
            Notification::make()
                ->title('This encounter field cannot be improved with AI.')
                ->danger()
                ->send();

            return;
        }

        $practiceId = PracticeContext::currentPracticeId();
        $fieldLabel = self::AI_IMPROVABLE_FIELDS[$field];
        $originalText = trim((string) data_get($this->data, $field, ''));

        if (! $practiceId) {
            Notification::make()
                ->title('Select a practice before using AI.')
                ->danger()
                ->send();

            return;
        }

        $suggestion = $this->createAISuggestion($practiceId, $originalText, 'pending', 'improve_field', [
            'field' => $field,
            'field_label' => $fieldLabel,
        ]);

        try {
            $suggestedText = $ai->improveField($originalText, $fieldLabel, [
                'field' => $field,
                'discipline' => $this->encounterAIValue('discipline'),
                'chief_complaint' => data_get($this->data, 'chief_complaint'),
            ]);

            $suggestion->update([
                'suggested_text' => $suggestedText,
                'status' => 'pending',
            ]);

            AIUsageLog::create([
                'practice_id' => $practiceId,
                'user_id' => auth()->id(),
                'feature' => 'improve_field',
                'status' => 'success',
            ]);

            $this->updateEncounterAIFormState([
                'active_ai_field' => $field,
                'active_ai_field_label' => $fieldLabel,
                'active_ai_suggestion' => $suggestedText,
                'active_ai_suggestion_id' => $suggestion->id,
            ]);

            Log::info('Encounter AI field suggestion state updated.', [
                'feature' => 'improve_field',
                'field' => $field,
                'practice_id' => $practiceId,
                'user_id' => auth()->id(),
                'suggestion_id' => $suggestion->id,
                'suggestion_length' => strlen($suggestedText),
            ]);

            Notification::make()
                ->title("AI suggestion ready for {$fieldLabel}.")
                ->success()
                ->send();
        } catch (Throwable $exception) {
            $suggestion->update([
                'status' => 'failed',
            ]);

            AIUsageLog::create([
                'practice_id' => $practiceId,
                'user_id' => auth()->id(),
                'feature' => 'improve_field',
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            Notification::make()
                ->title('Improve field is unavailable.')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function acceptFieldSuggestion(string $field): void
    {
        $activeField = data_get($this->data, 'active_ai_field');

        if (array_key_exists((string) $activeField, self::AI_IMPROVABLE_FIELDS)) {
            $field = (string) $activeField;
        }

        if (! array_key_exists($field, self::AI_IMPROVABLE_FIELDS)) {
            Notification::make()
                ->title('This encounter field cannot accept AI suggestions.')
                ->danger()
                ->send();

            return;
        }

        $suggestedText = trim((string) (
            data_get($this->data, 'active_ai_suggestion')
            ?? data_get($this->data, "ai_field_suggestions.{$field}.suggested_text", '')
        ));

        if ($suggestedText === '') {
            Notification::make()
                ->title('Generate an AI suggestion for this field before accepting.')
                ->danger()
                ->send();

            return;
        }

        $this->updateEncounterAIFormState([
            $field => $suggestedText,
        ]);

        $record = $this->encounterAIRecord();
        if ($record?->exists) {
            $record->update([$field => $suggestedText]);
        }

        $suggestionId = data_get($this->data, 'active_ai_suggestion_id')
            ?? data_get($this->data, "ai_field_suggestions.{$field}.suggestion_id");
        if ($suggestionId) {
            AISuggestion::whereKey($suggestionId)->update([
                'accepted_text' => $suggestedText,
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);
        }

        $this->clearActiveAIFieldSuggestion($field);

        $this->updateEncounterAIFormState([
            "ai_assisted_fields.{$field}" => true,
        ]);

        Notification::make()
            ->title('AI field suggestion accepted.')
            ->success()
            ->send();
    }

    public function dismissFieldSuggestion(string $field): void
    {
        $activeField = data_get($this->data, 'active_ai_field');

        if (array_key_exists((string) $activeField, self::AI_IMPROVABLE_FIELDS)) {
            $field = (string) $activeField;
        }

        if (! array_key_exists($field, self::AI_IMPROVABLE_FIELDS)) {
            return;
        }

        $suggestionId = data_get($this->data, 'active_ai_suggestion_id')
            ?? data_get($this->data, "ai_field_suggestions.{$field}.suggestion_id");
        if ($suggestionId) {
            AISuggestion::whereKey($suggestionId)->update([
                'status' => 'dismissed',
            ]);
        }

        $this->clearActiveAIFieldSuggestion($field);

        Notification::make()
            ->title('AI field suggestion dismissed.')
            ->success()
            ->send();
    }

    public function acceptActiveFieldSuggestion(): void
    {
        $field = (string) data_get($this->data, 'active_ai_field', '');

        $this->acceptFieldSuggestion($field);
    }

    public function dismissActiveFieldSuggestion(): void
    {
        $field = (string) data_get($this->data, 'active_ai_field', '');

        $this->dismissFieldSuggestion($field);
    }

    public function insuranceBillingEnabledForAI(): bool
    {
        $practiceId = PracticeContext::currentPracticeId();

        if (! $practiceId) {
            return false;
        }

        return (bool) Practice::query()
            ->whereKey($practiceId)
            ->value('insurance_billing_enabled');
    }

    private function createAISuggestion(int $practiceId, string $note, string $status, string $feature, array $context = []): AISuggestion
    {
        $record = $this->encounterAIRecord();

        return AISuggestion::create([
            'practice_id' => $practiceId,
            'user_id' => auth()->id(),
            'patient_id' => $record?->patient_id ?? data_get($this->data, 'patient_id'),
            'appointment_id' => $record?->appointment_id ?? data_get($this->data, 'appointment_id'),
            'encounter_id' => $record?->id,
            'feature' => $feature,
            'context_json' => $context ?: null,
            'original_text' => $note,
            'status' => $status,
        ]);
    }

    private function encounterAIRecord(): ?Encounter
    {
        $record = property_exists($this, 'record') ? $this->record : null;

        return $record instanceof Encounter ? $record : null;
    }

    private function encounterAIValue(string $key): mixed
    {
        return data_get($this->data, $key) ?? data_get($this->encounterAIRecord(), $key);
    }

    private function clearActiveAIFieldSuggestion(string $field): void
    {
        $this->updateEncounterAIFormState([
            'active_ai_field' => null,
            'active_ai_field_label' => null,
            'active_ai_suggestion' => null,
            'active_ai_suggestion_id' => null,
            "ai_field_suggestions.{$field}.suggested_text" => null,
            "ai_field_suggestions.{$field}.suggestion_id" => null,
        ]);
    }

    /**
     * Keep generated AI fields in Livewire form state without rehydrating the
     * entire record form, which can drop non-dehydrated review-only fields.
     */
    private function updateEncounterAIFormState(array $state): void
    {
        if (! is_array($this->data)) {
            $this->data = [];
        }

        foreach ($state as $key => $value) {
            data_set($this->data, $key, $value);
        }

        $this->form->fillPartially(
            $state,
            array_keys($state),
            shouldCallHydrationHooks: false,
            shouldFillStateWithNull: false,
        );
    }
}
