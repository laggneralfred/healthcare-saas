<?php

namespace App\Filament\Resources\SubscriptionPlans\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SubscriptionPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('key')
                ->label('Key')
                ->disabled(),

            TextInput::make('name')
                ->label('Name')
                ->disabled(),

            TextInput::make('price_monthly')
                ->label('Monthly Price (cents)')
                ->numeric()
                ->disabled(),

            TextInput::make('max_practitioners')
                ->label('Max Practitioners')
                ->disabled(),

            Toggle::make('is_active')
                ->label('Active')
                ->disabled(),

            TextInput::make('stripe_price_id')
                ->label('Stripe Price ID')
                ->helperText('Use the Stripe Price ID for this plan, such as a value starting with price_. Do not enter secret keys.')
                ->maxLength(255),
        ]);
    }
}
