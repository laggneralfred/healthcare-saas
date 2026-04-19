<?php

namespace App\Filament\Resources\Patients\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EncountersRelationManager extends RelationManager
{
    protected static string $relationship = 'encounters';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('visit_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('practitioner.user.name')
                    ->label('Practitioner')
                    ->placeholder('—'),

                TextColumn::make('chief_complaint')
                    ->label('Chief Complaint')
                    ->limit(40)
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'complete' => 'success',
                        'draft'    => 'warning',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state) => $state ? ucfirst($state) : '—'),
            ])
            ->defaultSort('visit_date', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DissociateAction::make()
                    ->hidden(fn () => auth()->user()?->isDemo()),
                DeleteAction::make()
                    ->hidden(fn () => auth()->user()?->isDemo()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make()
                        ->hidden(fn () => auth()->user()?->isDemo()),
                    DeleteBulkAction::make()
                        ->hidden(fn () => auth()->user()?->isDemo()),
                ]),
            ]);
    }
}
