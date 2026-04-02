<?php

namespace App\Filament\Resources\InventoryMovements\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class InventoryMovementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('practice_id')
                    ->default(fn () => auth()->user()->practice_id),
                TextInput::make('inventory_product_id')
                    ->required(),
                TextInput::make('type')
                    ->required(),
                TextInput::make('quantity')
                    ->required()
                    ->numeric(),
                TextInput::make('unit_price')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('reference'),
                Textarea::make('notes')
                    ->columnSpanFull(),
                TextInput::make('created_by')
                    ->numeric(),
            ]);
    }
}
