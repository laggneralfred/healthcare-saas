<?php

namespace App\Filament\Resources\Patients\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PatientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Patient Details')
                    ->schema([
                        Hidden::make('practice_id')
                            ->default(fn () => auth()->user()->practice_id),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(50),
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                        Toggle::make('is_patient')
                            ->default(true),
                    ]),

                Section::make('Communication Preferences')
                    ->relationship('communicationPreference')
                    ->schema([
                        Toggle::make('email_opt_in')
                            ->label('Email reminders')
                            ->default(true),

                        Toggle::make('sms_opt_in')
                            ->label('SMS reminders (coming soon)')
                            ->default(true)
                            ->disabled()
                            ->helperText('SMS reminders will be available in a future update.'),

                        Select::make('preferred_channel')
                            ->label('Preferred channel')
                            ->options(['email' => 'Email', 'both' => 'Email & SMS'])
                            ->default('email'),
                    ]),
            ]);
    }
}
