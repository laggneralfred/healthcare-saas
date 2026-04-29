<?php

namespace App\Filament\Resources\PracticePaymentMethods\Schemas;

use App\Models\CheckoutPayment;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PracticePaymentMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Placeholder::make('method')
                ->label('Method')
                ->content(fn ($record): string => CheckoutPayment::METHODS[$record?->method_key] ?? $record?->method_key ?? 'Payment method'),

            TextInput::make('display_name')
                ->label('Display Name')
                ->required()
                ->maxLength(255),

            Toggle::make('enabled')
                ->label('Enabled'),

            TextInput::make('sort_order')
                ->label('Sort Order')
                ->numeric()
                ->integer()
                ->required()
                ->default(0),
        ]);
    }
}
