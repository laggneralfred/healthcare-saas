<?php

namespace App\Filament\Resources\Encounters\Pages;

use App\Filament\Resources\CheckoutSessions\CheckoutSessionResource;
use App\Filament\Resources\Encounters\EncounterResource;
use App\Filament\Resources\Encounters\Widgets\EncounterHeader;
use App\Services\EncounterNoteDocument;
use App\Support\CheckoutWorkflow;
use App\Support\ClinicalStyle;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class ViewEncounter extends ViewRecord
{
    protected static string $resource = EncounterResource::class;

    public function getTitle(): string|Htmlable
    {
        return $this->getRecordTitle();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => static::getResource()::getUrl('index'))
                ->color('gray'),
            Action::make('edit')
                ->label('Edit Note')
                ->icon('heroicon-o-pencil')
                ->url(fn () => static::getResource()::getUrl('edit', ['record' => $this->record]))
                ->color('primary'),
            Action::make('checkout')
                ->label('Send to Checkout')
                ->icon('heroicon-o-shopping-cart')
                ->color('primary')
                ->action('sendToCheckout')
                ->visible(fn (): bool => $this->record->patient_id !== null),
        ];
    }

    public function sendToCheckout(): void
    {
        $user = auth()->user();

        if (! $user || ($user->cannot('update', $this->record) && $user->cannot('view', $this->record))) {
            Notification::make()
                ->title('You are not authorized to send this visit to checkout.')
                ->danger()
                ->send();

            return;
        }

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

    public function form(Schema $schema): Schema
    {
        return EncounterResource::form($schema);
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
