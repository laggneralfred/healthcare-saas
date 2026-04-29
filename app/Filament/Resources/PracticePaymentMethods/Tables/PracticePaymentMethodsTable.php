<?php

namespace App\Filament\Resources\PracticePaymentMethods\Tables;

use App\Models\CheckoutPayment;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PracticePaymentMethodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('method_key')
                    ->label('Method')
                    ->formatStateUsing(fn (string $state): string => CheckoutPayment::METHODS[$state] ?? $state)
                    ->sortable(),

                TextColumn::make('display_name')
                    ->label('Display Name')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('enabled')
                    ->label('Enabled')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('Sort')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('sort_order')
            ->emptyStateHeading('No payment methods configured')
            ->emptyStateDescription('Default payment methods are created automatically for each practice.');
    }
}
