<?php

namespace App\Filament\Resources\CheckoutSessions\Tables;

use App\Models\States\CheckoutSession\Draft;
use App\Models\States\CheckoutSession\Open;
use App\Models\States\CheckoutSession\Paid;
use App\Models\States\CheckoutSession\PaymentDue;
use App\Models\States\CheckoutSession\Voided;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CheckoutSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('practice.name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('patient.name')
                    ->label('Patient')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('charge_label')
                    ->label('Charge Label')
                    ->searchable(),

                TextColumn::make('state')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Draft::$name      => 'gray',
                        Open::$name       => 'info',
                        Paid::$name       => 'success',
                        PaymentDue::$name => 'warning',
                        Voided::$name       => 'danger',
                        default           => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Draft::$name      => 'Draft',
                        Open::$name       => 'Open',
                        Paid::$name       => 'Paid',
                        PaymentDue::$name => 'Payment Due',
                        Voided::$name       => 'Void',
                        default           => $state,
                    }),

                TextColumn::make('amount_total')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('tender_type')
                    ->label('Payment Method')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'cash'  => 'Cash',
                        'card'  => 'Card',
                        default => '—',
                    })
                    ->toggleable(),

                TextColumn::make('paid_on')
                    ->label('Paid On')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('started_on')
                    ->label('Started')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('state')
                    ->options([
                        Draft::$name      => 'Draft',
                        Open::$name       => 'Open',
                        Paid::$name       => 'Paid',
                        PaymentDue::$name => 'Payment Due',
                        Voided::$name       => 'Void',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('started_on', 'desc')
            ->emptyStateHeading('No checkouts yet')
            ->emptyStateDescription('Completed checkouts and payments will appear here.');
    }
}
