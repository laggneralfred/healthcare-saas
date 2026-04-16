<?php

namespace App\Filament\Resources\ConsentRecords\Pages;

use App\Filament\Resources\ConsentRecords\ConsentRecordResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewConsentRecord extends ViewRecord
{
    protected static string $resource = ConsentRecordResource::class;

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
            TextEntry::make('patient.name')
                ->label('Patient')
                ->extraAttributes(['style' => 'background-color: #fef3c7; padding: 0.75rem; border-radius: 0.375rem;']),

            TextEntry::make('status')
                ->label('Status')
                ->badge()
                ->color(fn ($state) => match($state) {
                    'complete' => 'success',
                    'missing' => 'danger',
                    default => 'gray',
                })
                ->extraAttributes(['style' => 'background-color: #fef3c7; padding: 0.75rem; border-radius: 0.375rem;']),

            TextEntry::make('consent_given_by')
                ->label('Consent Given By')
                ->placeholder('—')
                ->extraAttributes(['style' => 'background-color: #fef3c7; padding: 0.75rem; border-radius: 0.375rem;']),

            TextEntry::make('appointment_id')
                ->label('Appointment')
                ->getStateUsing(fn ($record) => $record->appointment
                    ? "#{$record->appointment->id} — {$record->appointment->start_datetime?->format('M j, Y g:ia')}"
                    : '—')
                ->placeholder('—'),

            TextEntry::make('signed_on')
                ->label('Signed On')
                ->dateTime('M j, Y g:i A')
                ->placeholder('—')
                ->extraAttributes(['style' => 'background-color: #fef3c7; padding: 0.75rem; border-radius: 0.375rem;']),

            TextEntry::make('signed_at_ip')
                ->label('Signed From IP')
                ->placeholder('—'),

            TextEntry::make('consent_summary')
                ->label('Consent Summary')
                ->placeholder('—'),

            TextEntry::make('notes')
                ->label('Notes')
                ->placeholder('—'),
        ]);
    }
}
