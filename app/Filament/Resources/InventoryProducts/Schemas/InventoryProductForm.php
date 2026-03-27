<?php

namespace App\Filament\Resources\InventoryProducts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Grid;
use Filament\Schemas\Schema;

class InventoryProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('sku')
                            ->label('SKU')
                            ->nullable(),
                        Select::make('category')
                            ->options([
                                'Herbal Formula' => 'Herbal Formula',
                                'Single Herb' => 'Single Herb',
                                'Supplement' => 'Supplement',
                                'Other' => 'Other',
                            ])
                            ->nullable()
                            ->searchable(),
                    ]),
                Textarea::make('description')
                    ->nullable()
                    ->columnSpanFull(),
                Grid::make(2)
                    ->schema([
                        Select::make('unit')
                            ->options([
                                'bottle' => 'Bottle',
                                'packet' => 'Packet',
                                'gram' => 'Gram',
                                'capsule' => 'Capsule',
                                'tablet' => 'Tablet',
                                'count' => 'Count',
                            ])
                            ->required()
                            ->searchable(),
                        TextInput::make('selling_price')
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->prefix('$'),
                    ]),
                Grid::make(2)
                    ->schema([
                        TextInput::make('cost_price')
                            ->numeric()
                            ->step(0.01)
                            ->prefix('$')
                            ->nullable(),
                        TextInput::make('low_stock_threshold')
                            ->numeric()
                            ->default(10)
                            ->helperText('Alert when stock reaches this level'),
                    ]),
                Grid::make(1)
                    ->schema([
                        Toggle::make('is_active')
                            ->default(true)
                            ->label('Active'),
                    ]),
            ]);
    }
}
