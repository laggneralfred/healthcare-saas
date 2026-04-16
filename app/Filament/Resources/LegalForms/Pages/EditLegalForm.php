<?php

namespace App\Filament\Resources\LegalForms\Pages;

use App\Filament\Resources\LegalForms\LegalFormResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLegalForm extends EditRecord
{
    protected static string $resource = LegalFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
