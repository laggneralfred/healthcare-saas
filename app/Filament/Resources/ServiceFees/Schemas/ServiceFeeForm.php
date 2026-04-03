<?php

namespace App\Filament\Resources\ServiceFees\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ServiceFeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('practice_id')
                ->default(fn () => auth()->user()->practice_id),

            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->disabledOn('view'),

            TextInput::make('short_description')
                ->maxLength(255)
                ->disabledOn('view'),

            TextInput::make('default_price')
                ->label('Default Price ($)')
                ->numeric()
                ->minValue(0)
                ->step(0.01)
                ->required()
                ->default(0)
                ->disabledOn('view'),

            Toggle::make('is_active')
                ->label('Active')
                ->default(true)
                ->disabledOn('view'),
        ]);
    }
}
