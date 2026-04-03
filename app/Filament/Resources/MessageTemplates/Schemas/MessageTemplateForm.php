<?php

namespace App\Filament\Resources\MessageTemplates\Schemas;

use App\Models\MessageTemplate;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MessageTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('practice_id')
                    ->default(fn () => auth()->user()->practice_id),

                Section::make('Template Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->disabledOn('view'),

                        Select::make('channel')
                            ->options(['email' => 'Email', 'sms' => 'SMS'])
                            ->default('email')
                            ->required()
                            ->live()
                            ->disabledOn('view'),

                        Select::make('trigger_event')
                            ->options(MessageTemplate::triggerEventLabels())
                            ->required()
                            ->disabledOn('view'),

                        TextInput::make('subject')
                            ->label('Subject line')
                            ->maxLength(255)
                            ->visible(fn ($get) => $get('channel') === 'email')
                            ->helperText('Available variables: {{patient_name}}, {{appointment_date}}, {{appointment_time}}, {{practitioner_name}}, {{practice_name}}, {{appointment_type}}')
                            ->disabledOn('view'),

                        Textarea::make('body')
                            ->required()
                            ->rows(10)
                            ->helperText('Available variables: {{patient_name}}, {{appointment_date}}, {{appointment_time}}, {{practitioner_name}}, {{practice_name}}, {{appointment_type}}')
                            ->disabledOn('view'),
                    ]),

                Section::make('Settings')
                    ->schema([
                        Toggle::make('is_active')
                            ->default(true)
                            ->label('Active')
                            ->disabledOn('view'),

                        Toggle::make('is_default')
                            ->label('Default template')
                            ->helperText('Default templates cannot be deleted')
                            ->disabledOn('view'),
                    ]),
            ]);
    }
}
