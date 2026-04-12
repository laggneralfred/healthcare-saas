<?php

namespace App\Filament\Resources\Encounters\Pages;

use App\Filament\Resources\Encounters\EncounterResource;
use App\Filament\Resources\Encounters\Schemas\EncounterForm;
use App\Filament\Resources\Encounters\Widgets\EncounterHeader;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

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
                ->label('Complete Encounter')
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
}
