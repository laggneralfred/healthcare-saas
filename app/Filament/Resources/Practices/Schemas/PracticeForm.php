<?php

namespace App\Filament\Resources\Practices\Schemas;

use App\Support\PracticeType;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PracticeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->disabledOn('view'),

                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(100)
                    ->helperText('URL-safe identifier, e.g. "green-valley-acupuncture"')
                    ->disabledOn('view'),

                TextInput::make('timezone')
                    ->required()
                    ->default('UTC')
                    ->maxLength(50)
                    ->disabledOn('view'),

                Hidden::make('discipline')
                    ->default('general'),

                Select::make('practice_type')
                    ->label('Practice Type')
                    ->options(PracticeType::options())
                    ->default(PracticeType::GENERAL_WELLNESS)
                    ->required()
                    ->helperText('Used to customize visit note templates and AI suggestions.')
                    ->disabledOn('view'),

                Toggle::make('is_active')
                    ->default(true)
                    ->disabledOn('view'),
            ]);
    }
}
