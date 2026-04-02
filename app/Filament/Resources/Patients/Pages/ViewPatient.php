<?php

namespace App\Filament\Resources\Patients\Pages;

use App\Filament\Resources\Patients\PatientResource;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewPatient extends ViewRecord
{
    protected static string $resource = PatientResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Personal Information')
                    ->columns(2)
                    ->schema([
                        Placeholder::make('full_name')
                            ->label('Full Name')
                            ->content(fn ($record) => trim("{$record->first_name} {$record->last_name}") ?: '—'),

                        Placeholder::make('preferred_name')
                            ->label('Preferred Name')
                            ->content(fn ($record) => $record->preferred_name ?: '—'),

                        Placeholder::make('dob')
                            ->label('Date of Birth')
                            ->content(fn ($record) => $record->dob
                                ? $record->dob->format('M j, Y') . '  (age ' . $record->dob->age . ')'
                                : '—'),

                        Placeholder::make('gender')
                            ->label('Gender')
                            ->content(fn ($record) => $record->gender ?: '—'),

                        Placeholder::make('pronouns')
                            ->label('Pronouns')
                            ->content(fn ($record) => $record->pronouns ?: '—'),
                    ]),

                Section::make('Contact Information')
                    ->columns(2)
                    ->schema([
                        Placeholder::make('email')
                            ->label('Email')
                            ->content(fn ($record) => $record->email ?: '—'),

                        Placeholder::make('phone')
                            ->label('Phone')
                            ->content(fn ($record) => $record->phone ?: '—'),

                        Placeholder::make('address')
                            ->label('Address')
                            ->columnSpan(2)
                            ->content(function ($record) {
                                $parts = array_filter([
                                    $record->address_line_1,
                                    $record->address_line_2,
                                    implode(', ', array_filter([$record->city, $record->state, $record->postal_code])),
                                    $record->country !== 'USA' ? $record->country : null,
                                ]);
                                return $parts ? implode("\n", $parts) : '—';
                            }),
                    ]),

                Section::make('Emergency Contact')
                    ->columns(2)
                    ->schema([
                        Placeholder::make('emergency_contact_name')
                            ->label('Contact Name')
                            ->content(fn ($record) => $record->emergency_contact_name ?: '—'),

                        Placeholder::make('emergency_contact_relationship')
                            ->label('Relationship')
                            ->content(fn ($record) => $record->emergency_contact_relationship ?: '—'),

                        Placeholder::make('emergency_contact_phone')
                            ->label('Contact Phone')
                            ->content(fn ($record) => $record->emergency_contact_phone ?: '—'),
                    ]),

                Section::make('Additional Information')
                    ->columns(2)
                    ->schema([
                        Placeholder::make('occupation')
                            ->label('Occupation')
                            ->content(fn ($record) => $record->occupation ?: '—'),

                        Placeholder::make('referred_by')
                            ->label('Referred By')
                            ->content(fn ($record) => $record->referred_by ?: '—'),

                        Placeholder::make('notes')
                            ->label('Notes')
                            ->columnSpan(2)
                            ->content(fn ($record) => $record->notes ?: '—'),
                    ]),

                Section::make('Summary')
                    ->columns(2)
                    ->schema([
                        Placeholder::make('appointments_count')
                            ->label('Total Appointments')
                            ->content(fn ($record) => $record->appointments()->count()),
                        Placeholder::make('practitioner.user.name')
                            ->label('Primary Practitioner')
                            ->content(fn ($record) => $record->appointments()->first()?->practitioner?->user?->name ?? 'None'),
                    ]),
            ]);
    }
}
