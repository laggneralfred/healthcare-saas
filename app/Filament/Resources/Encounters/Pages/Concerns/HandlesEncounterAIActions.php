<?php

namespace App\Filament\Resources\Encounters\Pages\Concerns;

use App\Models\AISuggestion;
use App\Models\AIUsageLog;
use App\Models\Encounter;
use App\Services\AI\AIService;
use App\Services\PracticeContext;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

trait HandlesEncounterAIActions
{
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

    private function createAISuggestion(int $practiceId, string $note, string $status, string $feature): AISuggestion
    {
        $record = $this->encounterAIRecord();

        return AISuggestion::create([
            'practice_id' => $practiceId,
            'user_id' => auth()->id(),
            'patient_id' => $record?->patient_id ?? data_get($this->data, 'patient_id'),
            'appointment_id' => $record?->appointment_id ?? data_get($this->data, 'appointment_id'),
            'encounter_id' => $record?->id,
            'feature' => $feature,
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
