<?php

namespace App\Filament\Resources\ServiceFees\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServiceFeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('practice.name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('short_description')
                    ->label('Description')
                    ->toggleable(),

                TextColumn::make('default_price')
                    ->label('Price')
                    ->money('USD')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}
