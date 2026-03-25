<?php

namespace App\Filament\Resources\ServiceFees\Pages;

use App\Filament\Resources\ServiceFees\ServiceFeeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServiceFees extends ListRecords
{
    protected static string $resource = ServiceFeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
