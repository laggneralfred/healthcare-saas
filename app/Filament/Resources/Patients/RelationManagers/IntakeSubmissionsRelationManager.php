<?php

namespace App\Filament\Resources\Patients\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IntakeSubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'intakeSubmissions';

    protected static ?string $title = 'Intake Forms';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('status')
            ->columns([
                TextColumn::make('discipline')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'acupuncture'   => 'info',
                        'massage'       => 'success',
                        'chiropractic'  => 'warning',
                        'physiotherapy' => 'primary',
                        default         => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'acupuncture'   => 'Acupuncture',
                        'massage'       => 'Massage',
                        'chiropractic'  => 'Chiropractic',
                        'physiotherapy' => 'Physiotherapy',
                        'general'       => 'General',
                        default         => '—',
                    })
                    ->placeholder('—'),

                TextColumn::make('chief_complaint')
                    ->label('Chief Complaint')
                    ->limit(50)
                    ->placeholder('—'),

                TextColumn::make('pain_scale')
                    ->label('Pain')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state <= 3     => 'success',
                        $state <= 6     => 'warning',
                        default         => 'danger',
                    })
                    ->formatStateUsing(fn ($state) => $state !== null ? "{$state}/10" : '—')
                    ->placeholder('—'),

                IconColumn::make('has_red_flags')
                    ->label('Flags')
                    ->getStateUsing(fn ($record) => $record->hasRedFlags())
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon(null)
                    ->trueColor('danger'),

                TextColumn::make('consent_given')
                    ->label('Consent')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state) => $state ? 'Signed' : 'Pending'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => $state === 'complete' ? 'success' : 'warning'),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->url(fn () => \App\Filament\Resources\IntakeSubmissions\IntakeSubmissionResource::getUrl('create', [
                        'patient_id' => $this->getOwnerRecord()->getKey(),
                    ])),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record) => \App\Filament\Resources\IntakeSubmissions\IntakeSubmissionResource::getUrl('view', ['record' => $record])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->hidden(fn () => auth()->user()?->isDemo()),
                ]),
            ]);
    }
}
