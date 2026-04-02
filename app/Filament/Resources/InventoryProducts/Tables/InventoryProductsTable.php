<?php

namespace App\Filament\Resources\InventoryProducts\Tables;

use App\Models\InventoryProduct;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('unit')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('selling_price')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->badge()
                    ->color(fn (InventoryProduct $record) => $record->isLowStock() ? 'danger' : 'success')
                    ->icon(fn (InventoryProduct $record) => $record->isLowStock() ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'Herbal Formula' => 'Herbal Formula',
                        'Single Herb' => 'Single Herb',
                        'Supplement' => 'Supplement',
                        'Other' => 'Other',
                    ]),
                SelectFilter::make('is_active')
                    ->options([
                        true => 'Active',
                        false => 'Inactive',
                    ])
                    ->label('Status'),
                Filter::make('low_stock')
                    ->query(fn (Builder $query) => $query->lowStock())
                    ->label('Low Stock'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('restock')
                    ->label('Restock')
                    ->icon('heroicon-m-plus-circle')
                    ->form([
                        TextInput::make('quantity')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->label('Quantity to Add'),
                        Textarea::make('notes')
                            ->nullable()
                            ->label('Notes'),
                    ])
                    ->after(fn () => redirect()->refresh())
                    ->action(function (InventoryProduct $record, array $data): void {
                        $record->movements()->create([
                            'practice_id' => $record->practice_id,
                            'type' => 'restock',
                            'quantity' => $data['quantity'],
                            'notes' => $data['notes'] ?? null,
                        ]);
                    })
                    ->successNotificationTitle('Inventory restocked successfully'),
                Action::make('adjust')
                    ->label('Adjust Stock')
                    ->icon('heroicon-m-adjustments-horizontal')
                    ->form([
                        TextInput::make('quantity')
                            ->required()
                            ->numeric()
                            ->label('Quantity Adjustment (positive or negative)'),
                        Textarea::make('notes')
                            ->nullable()
                            ->label('Reason for Adjustment'),
                    ])
                    ->after(fn () => redirect()->refresh())
                    ->action(function (InventoryProduct $record, array $data): void {
                        $record->movements()->create([
                            'practice_id' => $record->practice_id,
                            'type' => 'adjustment',
                            'quantity' => $data['quantity'],
                            'notes' => $data['notes'] ?? null,
                        ]);
                    })
                    ->successNotificationTitle('Stock adjusted successfully'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
