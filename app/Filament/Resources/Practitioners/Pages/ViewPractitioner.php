<?php

namespace App\Filament\Resources\Practitioners\Pages;

use App\Filament\Resources\Practitioners\PractitionerResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPractitioner extends ViewRecord
{
    protected static string $resource = PractitionerResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            PractitionerResource\Widgets\PractitionerStats::class,
        ];
    }
}
