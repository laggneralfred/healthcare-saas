<?php

namespace App\Filament\Resources\LegalAcceptances\Pages;

use App\Filament\Resources\LegalAcceptances\LegalAcceptanceResource;
use Filament\Resources\Pages\ListRecords;

class ListLegalAcceptances extends ListRecords
{
    protected static string $resource = LegalAcceptanceResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
