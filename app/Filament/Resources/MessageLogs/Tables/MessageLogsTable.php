<?php

namespace App\Filament\Resources\MessageLogs\Tables;

use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MessageLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.name')
                    ->label('Patient')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('channel')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'email' => 'info',
                        'sms'   => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => strtoupper($state)),

                TextColumn::make('messageTemplate.name')
                    ->label('Template')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('recipient')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending'   => 'gray',
                        'sent'      => 'info',
                        'delivered' => 'success',
                        'failed'    => 'danger',
                        'bounced'   => 'warning',
                        'opted_out' => 'warning',
                        default     => 'gray',
                    }),

                TextColumn::make('sent_at')
                    ->label('Sent')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('practitioner.user.name')
                    ->label('Practitioner')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending'   => 'Pending',
                        'sent'      => 'Sent',
                        'delivered' => 'Delivered',
                        'failed'    => 'Failed',
                        'bounced'   => 'Bounced',
                        'opted_out' => 'Opted Out',
                    ]),

                SelectFilter::make('channel')
                    ->options(['email' => 'Email', 'sms' => 'SMS']),
            ])
            ->recordActions([
                Action::make('view_body')
                    ->label('View Message')
                    ->icon('heroicon-m-eye')
                    ->modalContent(fn ($record) => view('filament.modals.message-log-body', ['log' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->emptyStateHeading('No messages sent yet')
            ->emptyStateDescription('Sent, queued, and failed messages will appear here.');
    }
}
