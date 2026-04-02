<?php

namespace App\Filament\Resources\ConsentRecords\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ConsentRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('practice_id')
                    ->default(fn () => auth()->user()->practice_id),

                Select::make('patient_id')
                    ->relationship('patient', 'name')
                    ->searchable()->preload()->required(),

                Select::make('appointment_id')
                    ->relationship('appointment', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($r) => "#{$r->id} — {$r->start_datetime?->format('M j, Y g:ia')}")
                    ->searchable()->nullable(),

                Select::make('status')
                    ->options(['missing' => 'Missing', 'complete' => 'Complete'])
                    ->default('missing')
                    ->required(),

                Placeholder::make('access_token')
                    ->label('Share link token')
                    ->content(fn ($record) => $record ? $record->access_token : '(generated on save)'),

                TextInput::make('consent_given_by')
                    ->label('Consent given by')
                    ->maxLength(255)
                    ->nullable(),

                Textarea::make('consent_summary')->rows(3)->nullable(),
                Textarea::make('notes')->rows(2)->nullable(),
            ]);
    }
}
