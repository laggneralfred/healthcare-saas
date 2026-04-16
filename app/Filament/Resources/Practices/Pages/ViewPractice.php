<?php

namespace App\Filament\Resources\Practices\Pages;

use App\Filament\Resources\Practices\PracticeResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewPractice extends ViewRecord
{
    protected static string $resource = PracticeResource::class;

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

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name')
                ->label('Practice Name')
                ->extraAttributes(['style' => 'background-color: #fef3c7; padding: 0.75rem; border-radius: 0.375rem;']),

            TextEntry::make('slug')
                ->label('URL Slug')
                ->extraAttributes(['style' => 'background-color: #fef3c7; padding: 0.75rem; border-radius: 0.375rem;']),

            TextEntry::make('discipline')
                ->label('Primary Discipline')
                ->formatStateUsing(fn ($state) => match($state) {
                    'acupuncture' => 'Acupuncture',
                    'massage' => 'Massage Therapy',
                    'chiropractic' => 'Chiropractic',
                    'physiotherapy' => 'Physiotherapy',
                    'general' => 'General',
                    default => $state,
                })
                ->extraAttributes(['style' => 'background-color: #fef3c7; padding: 0.75rem; border-radius: 0.375rem;']),

            TextEntry::make('timezone')
                ->label('Timezone')
                ->placeholder('—'),

            TextEntry::make('is_active')
                ->label('Active')
                ->badge()
                ->color(fn ($state) => $state ? 'success' : 'danger'),
        ]);
    }
}
