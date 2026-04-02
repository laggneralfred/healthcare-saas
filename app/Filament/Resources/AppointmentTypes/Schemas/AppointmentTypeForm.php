<?php

namespace App\Filament\Resources\AppointmentTypes\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AppointmentTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('practice_id')
                    ->default(fn () => auth()->user()->practice_id),

                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
