<?php

namespace App\Filament\Resources\NewPatientInterests\Tables;

use App\Models\NewPatientInterest;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NewPatientInterestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['last_name', 'first_name']),

                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('preferred_service')
                    ->label('Preferred Service')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        NewPatientInterest::STATUS_NEW => 'info',
                        NewPatientInterest::STATUS_REVIEWING => 'warning',
                        NewPatientInterest::STATUS_FORMS_SENT => 'primary',
                        NewPatientInterest::STATUS_CONVERTED => 'success',
                        NewPatientInterest::STATUS_DECLINED => 'danger',
                        NewPatientInterest::STATUS_CLOSED => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => NewPatientInterest::STATUS_OPTIONS[$state] ?? str($state)->replace('_', ' ')->title()),

                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(NewPatientInterest::STATUS_OPTIONS),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('mark_reviewing')
                    ->label('Mark Reviewing')
                    ->icon('heroicon-m-eye')
                    ->color('warning')
                    ->visible(fn (NewPatientInterest $record): bool => $record->status !== NewPatientInterest::STATUS_REVIEWING)
                    ->action(fn (NewPatientInterest $record) => $record->update(['status' => NewPatientInterest::STATUS_REVIEWING])),
                Action::make('mark_declined')
                    ->label('Decline')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (NewPatientInterest $record) => $record->update([
                        'status' => NewPatientInterest::STATUS_DECLINED,
                        'responded_at' => now(),
                        'responded_by_user_id' => auth()->id(),
                    ])),
                Action::make('mark_closed')
                    ->label('Close')
                    ->icon('heroicon-m-archive-box')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(fn (NewPatientInterest $record) => $record->update([
                        'status' => NewPatientInterest::STATUS_CLOSED,
                        'responded_at' => now(),
                        'responded_by_user_id' => auth()->id(),
                    ])),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No new patient interests yet')
            ->emptyStateDescription('Public new-patient requests will appear here for staff review.');
    }
}
