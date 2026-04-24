<?php

namespace App\Filament\Resources\Encounters\Pages;

use App\Filament\Resources\Encounters\EncounterResource;
use App\Filament\Resources\Encounters\Schemas\EncounterForm;
use App\Filament\Resources\Encounters\Widgets\EncounterHeader;
use App\Models\AISuggestion;
use App\Models\AIUsageLog;
use App\Services\AI\AIService;
use App\Services\PracticeContext;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class EditEncounter extends EditRecord
{
    protected static string $resource = EncounterResource::class;

    public function form(Schema $schema): Schema
    {
        return EncounterResource::form($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('saveDraft')
                ->label('Save Draft')
                ->color('gray')
                ->action('saveDraft'),

            Action::make('complete')
                ->label('Complete Visit')
                ->color('success')
                ->action('completeEncounter'),

            Action::make('checkout')
                ->label('Proceed to Checkout')
                ->color('primary')
                ->action('proceedToCheckout'),
        ];
    }

    public function saveDraft(): void
    {
        $data = $this->form->getState();
        $this->record->update($data);
        $this->record->update(['status' => 'draft']);
        $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
    }

    public function completeEncounter(): void
    {
        $data = $this->form->getState();
        $this->record->update($data);
        $this->record->update(['status' => 'complete']);
        $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
    }

    public function proceedToCheckout(): void
    {
        $data = $this->form->getState();
        $this->record->update($data);
        $this->record->update(['status' => 'complete']);

        // Find or create a checkout session for this encounter's appointment
        if ($this->record->appointment) {
            $checkout = $this->record->appointment->checkoutSession;
            if (!$checkout) {
                $checkout = $this->record->appointment->checkoutSession()->create([
                    'practice_id' => auth()->user()->practice_id,
                    'status' => 'open',
                ]);
            }
            $this->redirect('/admin/checkout-sessions/' . $checkout->id . '/edit');
        } else {
            // No appointment linked, redirect to edit view
            $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
        }
    }

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
                'discipline' => $this->record->discipline,
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

            data_set($this->data, 'ai_suggestion', $suggestedText);
            data_set($this->data, 'ai_suggestion_id', $suggestion->id);
            $this->form->fill($this->data);

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

        data_set($this->data, 'visit_notes', $suggestedText);
        $this->form->fill($this->data);
        $this->record->update(['visit_notes' => $suggestedText]);

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
                'discipline' => $this->record->discipline,
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

            data_set($this->data, 'documentation_check_result', $checklist);
            $this->form->fill($this->data);

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

    protected function getHeaderWidgets(): array
    {
        return [
            EncounterHeader::class,
        ];
    }

    protected function resolveRecord($key): Model
    {
        return parent::resolveRecord($key)->load('acupunctureEncounter');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Flatten acupunctureEncounter relationship data into form state
        $record = $this->record;
        if ($record->acupunctureEncounter) {
            $acu = $record->acupunctureEncounter->toArray();
            foreach ($acu as $key => $value) {
                $data["acupunctureEncounter.$key"] = $value;
            }
        }

        return $data;
    }

    private function createAISuggestion(int $practiceId, string $note, string $status, string $feature): AISuggestion
    {
        return AISuggestion::create([
            'practice_id' => $practiceId,
            'user_id' => auth()->id(),
            'patient_id' => $this->record->patient_id,
            'appointment_id' => $this->record->appointment_id,
            'encounter_id' => $this->record->id,
            'feature' => $feature,
            'original_text' => $note,
            'status' => $status,
        ]);
    }
}
