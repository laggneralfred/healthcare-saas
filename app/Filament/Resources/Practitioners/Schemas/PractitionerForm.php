<?php

namespace App\Filament\Resources\Practitioners\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PractitionerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('practice_id')
                    ->default(fn () => auth()->user()->practice_id),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabledOn('view'),
                TextInput::make('license_number')
                    ->maxLength(100)
                    ->disabledOn('view'),
                TextInput::make('specialty')
                    ->maxLength(150)
                    ->disabledOn('view'),
                Toggle::make('is_active')
                    ->default(true)
                    ->disabledOn('view'),
            ]);
    }
}
