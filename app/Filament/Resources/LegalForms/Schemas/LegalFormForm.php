<?php

namespace App\Filament\Resources\LegalForms\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LegalFormForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('practice_id')
                    ->default(fn () => auth()->user()->practice_id),

                Section::make('Legal Form Details')
                    ->schema([
                        Select::make('discipline')
                            ->options([
                                'acupuncture' => 'Acupuncture',
                                'massage' => 'Massage Therapy',
                                'chiropractic' => 'Chiropractic',
                                'physiotherapy' => 'Physiotherapy',
                            ])
                            ->required()
                            ->disabled(fn ($record) => $record !== null),

                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),

                        RichEditor::make('body')
                            ->required()
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->default(true)
                            ->helperText('Only one active form per discipline is allowed'),
                    ])
                    ->columns(2),
            ]);
    }
}
