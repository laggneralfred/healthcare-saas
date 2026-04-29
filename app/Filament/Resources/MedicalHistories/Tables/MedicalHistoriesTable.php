<?php

namespace App\Filament\Resources\MedicalHistories\Tables;

use App\Support\PracticeType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MedicalHistoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.name')
                    ->label('Patient')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('practice.practice_type')
                    ->label('Practice Type')
                    ->badge()
                    ->color(fn ($record) => match (PracticeType::fromPractice($record?->practice)) {
                        PracticeType::TCM_ACUPUNCTURE, PracticeType::FIVE_ELEMENT_ACUPUNCTURE => 'info',
                        PracticeType::MASSAGE_THERAPY => 'success',
                        PracticeType::CHIROPRACTIC => 'warning',
                        PracticeType::PHYSIOTHERAPY => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state, $record) => PracticeType::label(
                        PracticeType::fromPractice($record?->practice),
                    ))
                    ->placeholder('—'),

                TextColumn::make('chief_complaint')
                    ->label('Chief Complaint')
                    ->limit(50)
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('pain_scale')
                    ->label('Pain')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state <= 3 => 'success',
                        $state <= 6 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn ($state) => $state !== null ? "{$state}/10" : '—')
                    ->placeholder('—'),

                IconColumn::make('has_red_flags')
                    ->label('Red Flags')
                    ->getStateUsing(fn ($record) => $record->hasRedFlags())
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon(null)
                    ->trueColor('danger')
                    ->tooltip(fn ($record) => $record->hasRedFlags() ? 'Red flags present — review before treatment' : null),

                TextColumn::make('consent_given')
                    ->label('Consent')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state) => $state ? 'Signed' : 'Pending'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => $state === 'complete' ? 'success' : 'warning'),

                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('discipline')
                    ->label('Legacy Category')
                    ->options([
                        'acupuncture' => 'Acupuncture',
                        'massage' => 'Massage Therapy',
                        'chiropractic' => 'Chiropractic',
                        'physiotherapy' => 'Physiotherapy',
                        'general' => 'General',
                    ]),

                TernaryFilter::make('has_red_flags')
                    ->label('Red Flags')
                    ->queries(
                        true: fn ($query) => $query->withRedFlags(),
                        false: fn ($query) => $query->whereDoesntHave('patient')->orWhere(function ($q) {
                            $q->where('is_pregnant', false)
                                ->where('has_pacemaker', false)
                                ->where('takes_blood_thinners', false)
                                ->where('has_bleeding_disorder', false)
                                ->where('has_infectious_disease', false);
                        }),
                    ),

                TernaryFilter::make('consent_given')
                    ->label('Consent')
                    ->queries(
                        true: fn ($query) => $query->withConsent(),
                        false: fn ($query) => $query->pendingConsent(),
                    ),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No medical histories yet')
            ->emptyStateDescription('Intake submissions will appear here once patients complete their forms.')
            ->emptyStateActions([
                CreateAction::make()->label('New medical history'),
            ]);
    }
}
