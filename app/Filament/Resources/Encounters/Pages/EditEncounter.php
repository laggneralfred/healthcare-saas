<?php

namespace App\Filament\Resources\Encounters\Pages;

use App\Filament\Resources\Encounters\EncounterResource;
use App\Filament\Resources\Encounters\Pages\Concerns\HandlesEncounterAIActions;
use App\Filament\Resources\Encounters\Widgets\EncounterHeader;
use App\Services\EncounterDataValidator;
use App\Services\EncounterNoteDocument;
use App\Services\PracticeContext;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class EditEncounter extends EditRecord
{
    use HandlesEncounterAIActions;

    protected static string $resource = EncounterResource::class;

    public function getTitle(): string|Htmlable
    {
        return $this->getRecordTitle();
    }

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
                ->label('Save Note')
                ->color('primary')
                ->action('saveDraft')
                ->keyBindings(['mod+s']),

            Action::make('complete')
                ->label('Complete Note')
                ->color('success')
                ->action('completeEncounter')
                ->visible(fn (): bool => $this->record->status !== 'complete'),

            Action::make('reopen')
                ->label('Reopen Note')
                ->color('gray')
                ->action('reopenEncounter')
                ->visible(fn (): bool => $this->record->status === 'complete'),

            Action::make('checkout')
                ->label('Proceed to Checkout')
                ->color('primary')
                ->action('proceedToCheckout'),
        ];
    }

    public function saveDraft(): void
    {
        $data = EncounterNoteDocument::applyToEncounterData($this->form->getState(), ! $this->insuranceBillingEnabledForAI());
        $data = EncounterDataValidator::forCurrentPractice($data);
        $this->record->update($data);
        $this->record->refresh();

        Notification::make()
            ->title('Note saved.')
            ->success()
            ->send();
    }

    public function completeEncounter(): void
    {
        $data = EncounterNoteDocument::applyToEncounterData($this->form->getState(), ! $this->insuranceBillingEnabledForAI());
        $data = EncounterDataValidator::forCurrentPractice($data);
        $this->record->update($data);
        $this->record->update([
            'status' => 'complete',
            'completed_on' => now(),
        ]);
        $this->record->refresh();

        Notification::make()
            ->title('Note completed.')
            ->success()
            ->send();
    }

    public function reopenEncounter(): void
    {
        $this->record->update([
            'status' => 'draft',
            'completed_on' => null,
        ]);
        $this->record->refresh();

        Notification::make()
            ->title('Note reopened.')
            ->success()
            ->send();
    }

    public function proceedToCheckout(): void
    {
        $data = EncounterNoteDocument::applyToEncounterData($this->form->getState(), ! $this->insuranceBillingEnabledForAI());
        $data = EncounterDataValidator::forCurrentPractice($data);
        $this->record->update($data);

        // Find or create a checkout session for this encounter's appointment
        if ($this->record->appointment) {
            $checkout = $this->record->appointment->checkoutSession;
            if (! $checkout) {
                $checkout = $this->record->appointment->checkoutSession()->create([
                    'practice_id' => PracticeContext::currentPracticeId(),
                    'status' => 'open',
                ]);
            }
            $this->redirect('/admin/checkout-sessions/'.$checkout->id.'/edit');
        } else {
            // No appointment linked, redirect to edit view
            $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
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
        $data['visit_note_document'] = EncounterNoteDocument::fromFields(
            $data['chief_complaint'] ?? null,
            $data['visit_notes'] ?? null,
            $data['plan'] ?? null,
            $data['discipline'] ?? null,
        );

        if ($record->acupunctureEncounter) {
            $acu = $record->acupunctureEncounter->toArray();
            foreach ($acu as $key => $value) {
                $data["acupunctureEncounter.$key"] = $value;
            }
        }

        return $data;
    }
}
