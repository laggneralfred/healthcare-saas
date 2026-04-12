<?php

namespace App\Filament\Resources\Encounters\Pages;

use App\Filament\Resources\Encounters\EncounterResource;
use App\Filament\Resources\Encounters\Schemas\EncounterForm;
use App\Filament\Resources\Encounters\Widgets\EncounterHeader;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class ViewEncounter extends ViewRecord
{
    protected static string $resource = EncounterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => static::getResource()::getUrl('index'))
                ->color('gray'),
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
        if ($record->acupunctureEncounter) {
            $acu = $record->acupunctureEncounter->toArray();
            foreach ($acu as $key => $value) {
                $data["acupunctureEncounter.$key"] = $value;
            }
        }

        return $data;
    }
}
