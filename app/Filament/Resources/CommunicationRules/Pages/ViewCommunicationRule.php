<?php

namespace App\Filament\Resources\CommunicationRules\Pages;

use App\Filament\Resources\CommunicationRules\CommunicationRuleResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCommunicationRule extends ViewRecord
{
    protected static string $resource = CommunicationRuleResource::class;

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
