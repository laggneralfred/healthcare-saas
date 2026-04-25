<?php

namespace App\Filament\Resources\Encounters\Pages;

use App\Filament\Resources\Encounters\EncounterResource;
use App\Filament\Resources\Encounters\Widgets\EncounterHeader;
use App\Services\EncounterNoteDocument;
use Filament\Actions\Action;
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
        ];
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
