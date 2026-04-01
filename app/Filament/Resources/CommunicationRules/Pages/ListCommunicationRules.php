<?php

namespace App\Filament\Resources\CommunicationRules\Pages;

use App\Filament\Resources\CommunicationRules\CommunicationRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommunicationRules extends ListRecords
{
    protected static string $resource = CommunicationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
