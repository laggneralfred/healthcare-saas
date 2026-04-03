<?php

namespace App\Filament\Resources\Practices\Schemas;

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
                    ->maxLength(255),

                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(100)
                    ->helperText('URL-safe identifier, e.g. "green-valley-acupuncture"'),

                TextInput::make('timezone')
                    ->required()
                    ->default('UTC')
                    ->maxLength(50),

                Select::make('discipline')
                    ->label('Primary Discipline')
                    ->options([
                        'acupuncture'   => 'Acupuncture',
                        'massage'       => 'Massage Therapy',
                        'chiropractic'  => 'Chiropractic',
                        'physiotherapy' => 'Physiotherapy',
                        'general'       => 'General',
                    ])
                    ->default('acupuncture')
                    ->helperText('Sets the default intake form type for new submissions.'),

                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
