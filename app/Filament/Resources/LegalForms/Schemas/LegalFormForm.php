<?php

namespace App\Filament\Resources\LegalForms\Schemas;

use App\Support\PracticeType;
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
                            ->label('Form Category')
                            ->options([
                                'general' => 'General',
                                'acupuncture' => 'Acupuncture',
                                'massage' => 'Massage Therapy',
                                'chiropractic' => 'Chiropractic',
                                'physiotherapy' => 'Physiotherapy',
                            ])
                            ->default(fn () => PracticeType::disciplineFallback(
                                PracticeType::fromPractice(auth()->user()?->practice),
                            ))
                            ->helperText('Used to choose which kind of form this is for. Specific Practice Type is set on the practice.')
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
                            ->helperText('Only one active form per form category is allowed'),
                    ])
                    ->columns(2),
            ]);
    }
}
