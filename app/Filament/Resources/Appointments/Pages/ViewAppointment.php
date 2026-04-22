<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\States\Appointment\Cancelled;
use App\Models\States\Appointment\Checkout;
use App\Models\States\Appointment\Closed;
use App\Models\States\Appointment\Completed;
use App\Models\States\Appointment\InProgress;
use App\Models\States\Appointment\NoShow;
use App\Models\States\Appointment\Scheduled;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewAppointment extends ViewRecord
{
    protected static string $resource = AppointmentResource::class;

    public function getTitle(): string
    {
        return $this->record->patient->full_name ?? 'View Appointment';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => request('return_url') ?: static::getResource()::getUrl('index'))
                ->color('gray'),

            Action::make('edit')
                ->label('Edit')
                ->icon('heroicon-o-pencil')
                ->url(fn () => static::getResource()::getUrl('edit', [
                    'record' => $this->record,
                    'return_url' => request('return_url'),
                ]))
                ->color('gray'),

            // Check In: Scheduled → InProgress
            Action::make('check_in')
                ->label('Check In')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('success')
                ->visible(fn () => $this->record->status instanceof Scheduled)
                ->action(function () {
                    $this->record->status->transitionTo(InProgress::class);
                    Notification::make()->title('Appointment checked in.')->success()->send();
                    $this->refreshFormData(['status']);
                }),

            // No Show: Scheduled → NoShow
            Action::make('no_show')
                ->label('No Show')
                ->icon('heroicon-o-x-circle')
                ->color('warning')
                ->visible(fn () => $this->record->status instanceof Scheduled)
                ->requiresConfirmation()
                ->modalHeading('Mark as No Show?')
                ->modalDescription('This will mark the patient as a no-show for this appointment.')
                ->action(function () {
                    $this->record->status->transitionTo(NoShow::class);
                    Notification::make()->title('Marked as no show.')->warning()->send();
                    $this->refreshFormData(['status']);
                }),

            // Complete Visit: InProgress → Completed
            Action::make('complete_visit')
                ->label('Complete Visit')
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->visible(fn () => $this->record->status instanceof InProgress)
                ->action(function () {
                    $this->record->status->transitionTo(Completed::class);
                    Notification::make()->title('Visit completed.')->success()->send();
                    $this->refreshFormData(['status']);
                }),

            // Proceed to Checkout: Completed → Checkout
            Action::make('proceed_to_checkout')
                ->label('Proceed to Checkout')
                ->icon('heroicon-o-credit-card')
                ->color('primary')
                ->visible(fn () => $this->record->status instanceof Completed)
                ->action(function () {
                    $this->record->status->transitionTo(Checkout::class);
                    Notification::make()->title('Moved to checkout.')->success()->send();
                    $this->refreshFormData(['status']);
                }),

            // Close: Checkout → Closed
            Action::make('close')
                ->label('Close')
                ->icon('heroicon-o-lock-closed')
                ->color('gray')
                ->visible(fn () => $this->record->status instanceof Checkout)
                ->action(function () {
                    $this->record->status->transitionTo(Closed::class);
                    Notification::make()->title('Appointment closed.')->success()->send();
                    $this->refreshFormData(['status']);
                }),

            // Cancel: Scheduled or InProgress → Cancelled
            Action::make('cancel_appointment')
                ->label('Cancel')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(fn () => $this->record->status instanceof Scheduled
                    || $this->record->status instanceof InProgress)
                ->requiresConfirmation()
                ->modalHeading('Cancel Appointment?')
                ->modalDescription('Are you sure you want to cancel this appointment? This cannot be undone.')
                ->action(function () {
                    $this->record->status->transitionTo(Cancelled::class);
                    Notification::make()->title('Appointment cancelled.')->danger()->send();
                    $this->refreshFormData(['status']);
                }),
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
                ->color(fn ($state) => match (true) {
                    $state instanceof Scheduled  => 'info',
                    $state instanceof InProgress => 'warning',
                    $state instanceof Completed  => 'success',
                    $state instanceof Checkout   => 'primary',
                    $state instanceof Closed     => 'gray',
                    $state instanceof NoShow     => 'warning',
                    $state instanceof Cancelled  => 'danger',
                    default                      => 'gray',
                })
                ->formatStateUsing(fn ($state) => $state instanceof AppointmentState
                    ? $state->label()
                    : ucfirst(str_replace('_', ' ', (string) $state)))
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
