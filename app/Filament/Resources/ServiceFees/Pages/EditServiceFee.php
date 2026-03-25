<?php

namespace App\Filament\Resources\ServiceFees\Pages;

use App\Filament\Resources\ServiceFees\ServiceFeeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditServiceFee extends EditRecord
{
    protected static string $resource = ServiceFeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
