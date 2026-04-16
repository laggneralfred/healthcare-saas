<?php

namespace App\Filament\Resources\Practitioners\Pages;

use App\Filament\Resources\Practitioners\PractitionerResource;
use App\Filament\Resources\Practitioners\Widgets\PractitionerStats;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

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

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('user.name')
                ->label('Name')
                ->extraAttributes(['style' => 'background-color: #fef3c7; padding: 0.75rem; border-radius: 0.375rem;']),

            TextEntry::make('license_number')
                ->label('License Number')
                ->placeholder('—')
                ->extraAttributes(['style' => 'background-color: #fef3c7; padding: 0.75rem; border-radius: 0.375rem;']),

            TextEntry::make('specialty')
                ->label('Specialty')
                ->placeholder('—')
                ->extraAttributes(['style' => 'background-color: #fef3c7; padding: 0.75rem; border-radius: 0.375rem;']),

            TextEntry::make('is_active')
                ->label('Active')
                ->badge()
                ->color(fn ($state) => $state ? 'success' : 'danger'),

            TextEntry::make('user.email')
                ->label('Email')
                ->placeholder('—'),
        ]);
    }
}
