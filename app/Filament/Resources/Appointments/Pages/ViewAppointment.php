<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewAppointment extends ViewRecord
{
    protected static string $resource = AppointmentResource::class;

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

            TextEntry::make('practitioner.user.name')
                ->label('Practitioner')
                ->extraAttributes(['style' => 'background-color: #fef3c7; padding: 0.75rem; border-radius: 0.375rem;']),

            TextEntry::make('appointmentType.name')
                ->label('Appointment Type')
                ->extraAttributes(['style' => 'background-color: #fef3c7; padding: 0.75rem; border-radius: 0.375rem;']),

            TextEntry::make('start_datetime')
                ->label('Start Date & Time')
                ->dateTime('M j, Y g:i A')
                ->extraAttributes(['style' => 'background-color: #fef3c7; padding: 0.75rem; border-radius: 0.375rem;']),

            TextEntry::make('end_datetime')
                ->label('End Date & Time')
                ->dateTime('M j, Y g:i A'),

            TextEntry::make('status')
                ->label('Status')
                ->badge()
                ->color(fn ($state) => match($state::class) {
                    'App\Models\States\Appointment\Scheduled' => 'info',
                    'App\Models\States\Appointment\InProgress' => 'warning',
                    'App\Models\States\Appointment\Completed' => 'success',
                    'App\Models\States\Appointment\Closed' => 'gray',
                    'App\Models\States\Appointment\Checkout' => 'warning',
                    default => 'gray',
                })
                ->extraAttributes(['style' => 'background-color: #fef3c7; padding: 0.75rem; border-radius: 0.375rem;']),

            TextEntry::make('needs_follow_up')
                ->label('Needs Follow-up')
                ->badge()
                ->color(fn ($state) => $state ? 'warning' : 'success'),

            TextEntry::make('notes')
                ->label('Notes')
                ->placeholder('—'),
        ]);
    }
}
