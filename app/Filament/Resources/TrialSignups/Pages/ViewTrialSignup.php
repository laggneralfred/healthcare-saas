<?php

namespace App\Filament\Resources\TrialSignups\Pages;

use App\Filament\Resources\TrialSignups\TrialSignupResource;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewTrialSignup extends ViewRecord
{
    protected static string $resource = TrialSignupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => static::getResource()::getUrl('index'))
                ->color('gray'),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('signed_up_at')->label('Signed up')->dateTime(),
                TextEntry::make('name')->label('Name'),
                TextEntry::make('email')->label('Email'),
                TextEntry::make('phone')->label('Phone')->placeholder('—'),
                TextEntry::make('practice_name')->label('Practice name')->placeholder('—'),
                TextEntry::make('profession')->label('Profession')->placeholder('—'),
                TextEntry::make('practice_type')->label('Practice type')->placeholder('—'),
                TextEntry::make('heard_about_us')->label('Heard about us')->placeholder('—'),
                TextEntry::make('source')->label('Source')->placeholder('—'),
                TextEntry::make('practice.name')->label('Practice')->placeholder('—'),
                TextEntry::make('user.name')->label('User')->placeholder('—'),
                TextEntry::make('ip_address')->label('IP address')->placeholder('—'),
                TextEntry::make('user_agent')->label('User agent')->placeholder('—')->columnSpanFull(),
            ]);
    }
}
