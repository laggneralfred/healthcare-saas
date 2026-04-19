<?php

namespace App\Filament\Resources\CheckoutSessions\Pages;

use App\Filament\Resources\CheckoutSessions\CheckoutSessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCheckoutSession extends CreateRecord
{
    protected static string $resource = CheckoutSessionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['practice_id'] = auth()->user()->practice_id;
        return $data;
    }
}
