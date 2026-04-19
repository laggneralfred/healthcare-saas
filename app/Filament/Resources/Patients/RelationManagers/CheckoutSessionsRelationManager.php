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

class CheckoutSessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'checkoutSessions';

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
                TextColumn::make('created_at')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('charge_label')
                    ->label('Description')
                    ->limit(40)
                    ->placeholder('—'),

                TextColumn::make('amount_total')
                    ->label('Amount')
                    ->money('usd'),

                TextColumn::make('state')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ((string) $state) {
                        'paid'        => 'success',
                        'open'        => 'warning',
                        'payment_due' => 'danger',
                        'voided'      => 'gray',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', (string) $state))),
            ])
            ->defaultSort('created_at', 'desc')
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
