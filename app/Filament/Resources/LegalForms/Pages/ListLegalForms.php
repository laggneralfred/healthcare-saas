<?php

namespace App\Filament\Resources\LegalForms\Pages;

use App\Filament\Resources\LegalForms\LegalFormResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLegalForms extends ListRecords
{
    protected static string $resource = LegalFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
