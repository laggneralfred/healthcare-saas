<?php

namespace App\Filament\Resources\TrialSignups\Pages;

use App\Filament\Resources\TrialSignups\TrialSignupResource;
use Filament\Resources\Pages\ListRecords;

class ListTrialSignups extends ListRecords
{
    protected static string $resource = TrialSignupResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
