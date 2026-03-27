<?php

namespace App\Filament\Resources\InventoryMovements\Tables;

use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'restock',
                        'danger' => 'sale',
                        'warning' => 'adjustment',
                        'secondary' => 'return',
                    ])
                    ->icons([
                        'heroicon-m-arrow-up-circle' => 'restock',
                        'heroicon-m-arrow-down-circle' => 'sale',
                        'heroicon-m-adjustments-horizontal' => 'adjustment',
                        'heroicon-m-arrow-uturn-left' => 'return',
                    ]),
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable()
                    ->alignment('center'),
                TextColumn::make('unit_price')
                    ->money('USD')
                    ->nullable(),
                TextColumn::make('reference')
                    ->searchable()
                    ->nullable(),
                TextColumn::make('notes')
                    ->limit(50)
                    ->nullable(),
                TextColumn::make('createdByUser.name')
                    ->label('Created By')
                    ->nullable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'sale' => 'Sale',
                        'restock' => 'Restock',
                        'adjustment' => 'Adjustment',
                        'return' => 'Return',
                    ]),
                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('From'),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->label('Date Range'),
                SelectFilter::make('inventory_product_id')
                    ->relationship('product', 'name')
                    ->label('Product'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
