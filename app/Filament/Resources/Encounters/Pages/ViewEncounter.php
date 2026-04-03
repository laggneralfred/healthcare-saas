<?php

namespace App\Filament\Resources\Encounters\Pages;

use App\Filament\Resources\Encounters\EncounterResource;
use Filament\Resources\Pages\ViewRecord;

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
}
