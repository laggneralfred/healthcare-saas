<?php

namespace App\Filament\Resources\CheckoutSessions\Pages;

use App\Filament\Resources\CheckoutSessions\CheckoutSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCheckoutSessions extends ListRecords
{
    protected static string $resource = CheckoutSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
