<?php

namespace App\Filament\Resources\Practitioners\Tables;

use App\Support\PracticeType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PractitionersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('practice.name')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('license_number')
                    ->searchable(),
                TextColumn::make('specialty')
                    ->searchable(),
                TextColumn::make('clinical_style')
                    ->label('Clinical Style')
                    ->formatStateUsing(fn (?string $state): string => $state ? PracticeType::label($state) : 'Practice default')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? 'info' : 'gray'),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->emptyStateHeading('No practitioners yet')
            ->emptyStateDescription('Add a practitioner to start scheduling appointments.')
            ->emptyStateActions([
                CreateAction::make()->label('Add practitioner'),
            ]);
    }
}
