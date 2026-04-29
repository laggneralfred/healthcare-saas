<?php

namespace App\Filament\Resources\Practices\Tables;

use App\Support\PracticeType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PracticesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('practice_type')
                    ->label('Practice Type')
                    ->formatStateUsing(fn ($state, $record) => PracticeType::label(
                        PracticeType::normalize($state, $record?->discipline),
                    ))
                    ->sortable(),
                TextColumn::make('timezone')->sortable(),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No practices yet')
            ->emptyStateDescription('Create a practice to onboard a new tenant.')
            ->emptyStateActions([
                CreateAction::make()->label('Add practice'),
            ]);
    }
}
