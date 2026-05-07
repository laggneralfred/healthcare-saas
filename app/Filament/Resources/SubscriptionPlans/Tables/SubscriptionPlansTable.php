<?php

namespace App\Filament\Resources\SubscriptionPlans\Tables;

use App\Services\Billing\SubscriptionPlanCatalog;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriptionPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label('Key')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('price_monthly')
                    ->label('Price')
                    ->formatStateUsing(fn (int $state): string => '$'.number_format($state / 100, 0).'/month')
                    ->sortable(),

                TextColumn::make('billing_interval')
                    ->label('Interval')
                    ->state('Monthly'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('stripe_price_present')
                    ->label('Stripe Price')
                    ->state(fn ($record): bool => filled($record->stripe_price_id))
                    ->boolean(),

                TextColumn::make('stripe_price_id')
                    ->label('Price ID')
                    ->formatStateUsing(fn (?string $state): string => app(SubscriptionPlanCatalog::class)->mask($state))
                    ->placeholder('Missing'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('price_monthly')
            ->emptyStateHeading('No subscription plans configured')
            ->emptyStateDescription('Run php artisan billing:sync-stripe-prices to create the default plans.');
    }
}
