<?php

namespace App\Filament\Resources\Patients\Schemas;

use Filament\Forms\Components\DatePicker;
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
                Hidden::make('practice_id')
                    ->default(fn () => auth()->user()->practice_id),

                Section::make('Basic Information')
                    ->description('Core identity and contact details.')
                    ->schema([
                        TextInput::make('first_name')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('last_name')
                            ->required()
                            ->maxLength(100),
                        DatePicker::make('dob')
                            ->label('Date of Birth'),
                        Select::make('gender')
                            ->options([
                                'Male'             => 'Male',
                                'Female'           => 'Female',
                                'Non-binary'       => 'Non-binary',
                                'Prefer not to say' => 'Prefer not to say',
                                'Other'            => 'Other',
                            ]),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email('rfc,dns')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(50)
                            ->rule('regex:/^\+?1?[-.\s]?\(?[0-9]{3}\)?[-.\s]?[0-9]{3}[-.\s]?[0-9]{4}$/')
                            ->helperText('Format: (555) 123-4567 or +1-555-123-4567'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Additional Identity')
                    ->schema([
                        TextInput::make('middle_name')
                            ->maxLength(100),
                        TextInput::make('preferred_name')
                            ->label('Preferred Name / Goes by')
                            ->maxLength(100),
                        TextInput::make('pronouns')
                            ->label('Pronouns')
                            ->placeholder('e.g. He/Him, She/Her, They/Them')
                            ->maxLength(50)
                            ->columnSpan(2),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(true),

                Section::make('Address')
                    ->schema([
                        TextInput::make('address_line_1')
                            ->label('Address Line 1')
                            ->maxLength(255)
                            ->columnSpan(2),
                        TextInput::make('address_line_2')
                            ->label('Address Line 2 (Apt, Suite, Unit)')
                            ->maxLength(255)
                            ->columnSpan(2),
                        TextInput::make('city')
                            ->maxLength(100),
                        Select::make('state')
                            ->options(self::usStates())
                            ->searchable(),
                        TextInput::make('postal_code')
                            ->label('ZIP / Postal Code')
                            ->maxLength(20),
                        TextInput::make('country')
                            ->maxLength(100)
                            ->default('USA'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(true),

                Section::make('Emergency Contact')
                    ->schema([
                        TextInput::make('emergency_contact_name')
                            ->label('Contact Name')
                            ->maxLength(255),
                        TextInput::make('emergency_contact_relationship')
                            ->label('Relationship')
                            ->maxLength(100),
                        TextInput::make('emergency_contact_phone')
                            ->label('Contact Phone')
                            ->tel()
                            ->maxLength(50)
                            ->columnSpan(2),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(true),

                Section::make('Additional Information')
                    ->schema([
                        TextInput::make('occupation')
                            ->maxLength(255),
                        TextInput::make('referred_by')
                            ->label('How did they hear about you?')
                            ->maxLength(255),
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpan(2),
                        Toggle::make('is_patient')
                            ->default(true)
                            ->columnSpan(2),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(true),

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
                    ])
                    ->collapsible()
                    ->collapsed(true),
            ]);
    }

    private static function usStates(): array
    {
        return [
            'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
            'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
            'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
            'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
            'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
            'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
            'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
            'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
            'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
            'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
            'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
            'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
            'WI' => 'Wisconsin', 'WY' => 'Wyoming', 'DC' => 'District of Columbia',
            // Canadian provinces
            'AB' => 'Alberta', 'BC' => 'British Columbia', 'MB' => 'Manitoba',
            'NB' => 'New Brunswick', 'NL' => 'Newfoundland and Labrador', 'NS' => 'Nova Scotia',
            'ON' => 'Ontario', 'PE' => 'Prince Edward Island', 'QC' => 'Quebec',
            'SK' => 'Saskatchewan',
        ];
    }
}
