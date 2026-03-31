<?php

namespace App\Filament\Resources\IntakeSubmissions\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class IntakeSubmissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('practice_id')
                    ->relationship('practice', 'name')
                    ->searchable()->preload()->required(),

                Select::make('patient_id')
                    ->relationship('patient', 'name')
                    ->searchable()->preload()->required(),

                Select::make('appointment_id')
                    ->relationship('appointment', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($r) => "#{$r?->id} — {$r?->start_datetime?->format('M j, Y g:ia')}")
                    ->searchable()->nullable(),

                Select::make('status')
                    ->options(['missing' => 'Missing', 'complete' => 'Complete'])
                    ->default('missing')
                    ->required(),

                Placeholder::make('access_token')
                    ->label('Share link token')
                    ->content(fn ($record) => $record?->access_token ?? '(generated on save)'),

                Textarea::make('reason_for_visit')->rows(2)->nullable(),
                Textarea::make('current_concerns')->rows(2)->nullable(),
                Textarea::make('relevant_history')->rows(2)->nullable(),
                Textarea::make('medications')->rows(2)->nullable(),
                Textarea::make('notes')->rows(2)->nullable(),
                Textarea::make('summary_text')->label('Summary')->rows(3)->nullable(),
            ]);
    }
}
