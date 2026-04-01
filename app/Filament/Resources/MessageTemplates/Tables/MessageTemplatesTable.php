<?php

namespace App\Filament\Resources\MessageTemplates\Tables;

use App\Models\MessageTemplate;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MessageTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('channel')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'email' => 'info',
                        'sms'   => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => strtoupper($state)),

                TextColumn::make('trigger_event')
                    ->label('Trigger')
                    ->formatStateUsing(fn ($state) => MessageTemplate::triggerEventLabels()[$state] ?? $state)
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('is_default')
                    ->label('Default')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Default' : '')
                    ->color('warning'),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->options(['email' => 'Email', 'sms' => 'SMS']),

                SelectFilter::make('trigger_event')
                    ->options(MessageTemplate::triggerEventLabels()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->using(function ($records) {
                            $records->each(function ($record) {
                                if (! $record->is_default) {
                                    $record->delete();
                                }
                            });
                        }),
                ]),
            ]);
    }
}
