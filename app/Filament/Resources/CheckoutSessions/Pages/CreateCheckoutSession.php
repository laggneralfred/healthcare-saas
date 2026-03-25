<?php

namespace App\Filament\Resources\CheckoutSessions\Pages;

use App\Filament\Resources\CheckoutSessions\CheckoutSessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCheckoutSession extends CreateRecord
{
    protected static string $resource = CheckoutSessionResource::class;
}
