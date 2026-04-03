<?php

namespace App\Filament\Resources\Practitioners\Pages;

use App\Filament\Resources\Practitioners\PractitionerResource;
use App\Filament\Resources\Practitioners\Widgets\PractitionerStats;
use Filament\Resources\Pages\ViewRecord;

class ViewPractitioner extends ViewRecord
{
    protected static string $resource = PractitionerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => static::getResource()::getUrl('index'))
                ->color('gray'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PractitionerStats::class,
        ];
    }
}
