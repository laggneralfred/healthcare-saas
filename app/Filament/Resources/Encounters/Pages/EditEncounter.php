<?php

namespace App\Filament\Resources\Encounters\Pages;

use App\Filament\Resources\CheckoutSessions\CheckoutSessionResource;
use App\Filament\Resources\Encounters\EncounterResource;
use App\Filament\Resources\Encounters\Pages\Concerns\HandlesEncounterAIActions;
use App\Filament\Resources\Encounters\Widgets\EncounterHeader;
use App\Services\EncounterDataValidator;
use App\Services\EncounterNoteDocument;
use App\Support\CheckoutWorkflow;
use App\Support\ClinicalStyle;
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

    public static bool $formActionsAreSticky = true;

    public bool $noteSaved = false;

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
                ->label('Send to Checkout')
                ->color('primary')
                ->action('proceedToCheckout')
                ->visible(fn (): bool => $this->noteSaved && $this->record->patient_id !== null),

            Action::make('done')
                ->label('Done')
                ->color('gray')
                ->url(fn (): string => static::getResource()::getUrl('view', ['record' => $this->record]))
                ->visible(fn (): bool => $this->noteSaved && $this->record->appointment === null),
        ];
    }

    public function saveDraft(): void
    {
        $data = EncounterNoteDocument::applyToEncounterData($this->form->getState(), ! $this->insuranceBillingEnabledForAI());
        $data = EncounterDataValidator::forCurrentPractice($data);
        $this->record->update($data);
        $this->record->refresh();
        $this->noteSaved = true;

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
        $this->noteSaved = true;

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
        $user = auth()->user();

        if (! $user || ($user->cannot('update', $this->record) && $user->cannot('view', $this->record))) {
            Notification::make()
                ->title('You are not authorized to send this visit to checkout.')
                ->danger()
                ->send();

            return;
        }

        $data = EncounterNoteDocument::applyToEncounterData($this->form->getState(), ! $this->insuranceBillingEnabledForAI());
        $data = EncounterDataValidator::forCurrentPractice($data);
        $this->record->update($data);
        $this->record->refresh();

        $checkout = CheckoutWorkflow::sessionForEncounter($this->record);

        if (! $checkout) {
            Notification::make()
                ->title('Checkout cannot be opened without a patient.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Sent to front desk for checkout.')
            ->success()
            ->send();

        if ($user->canManageOperations()) {
            $this->redirect(CheckoutSessionResource::getUrl('edit', ['record' => $checkout]));
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
        return parent::resolveRecord($key)->load(['acupunctureEncounter', 'practice', 'practitioner']);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Flatten acupunctureEncounter relationship data into form state
        $record = $this->record;
        $data['visit_note_document'] = EncounterNoteDocument::fromFields(
            $data['chief_complaint'] ?? null,
            $data['visit_notes'] ?? null,
            $data['plan'] ?? null,
            ClinicalStyle::fromEncounter($record),
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
