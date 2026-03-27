<?php

namespace App\Filament\Resources\CheckoutSessions\Schemas;

use App\Models\States\CheckoutSession\Open;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CheckoutSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Session Details')->schema([
                Select::make('practice_id')
                    ->relationship('practice', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabledOn('edit'),

                Select::make('appointment_id')
                    ->relationship('appointment', 'id')
                    ->getOptionLabelFromRecordUsing(
                        fn ($record) => "{$record->patient?->name} — {$record->start_datetime?->format('M d, Y H:i')}"
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabledOn('edit'),

                TextInput::make('charge_label')
                    ->required()
                    ->maxLength(255)
                    ->default('Visit Charges'),

                Textarea::make('notes')
                    ->rows(2)
                    ->nullable(),
            ])->columns(2),

            Section::make('Line Items')->schema([
                Repeater::make('checkoutLines')
                    ->relationship()
                    ->schema([
                        TextInput::make('description')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        TextInput::make('amount')
                            ->label('Amount ($)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->required()
                            ->default(0),
                    ])
                    ->columns(3)
                    ->defaultItems(1)
                    ->addable(fn ($record) => $record === null || $record->isEditable())
                    ->deletable(fn ($record) => $record === null || $record->isEditable())
                    ->reorderable(false)
                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data, $record): array {
                        $data['practice_id'] = $record->practice_id;
                        return $data;
                    })
                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data, $record): array {
                        $data['practice_id'] = $record->practice_id;
                        return $data;
                    }),

                Placeholder::make('amount_total_display')
                    ->label('Session Total')
                    ->content(fn ($record) => $record
                        ? '$' . number_format((float) $record->amount_total, 2)
                        : '$0.00'
                    ),
            ]),

            Section::make('Add Products')
                ->visible(fn (Get $get, $record) => $record && $record->practice && $record->practice->hasInventoryAddon())
                ->description('Select inventory products to add to this checkout.')
                ->schema([
                    Repeater::make('inventoryProducts')
                        ->schema([
                            Select::make('inventory_product_id')
                                ->label('Product')
                                ->options(function (Get $get, $record) {
                                    if (!$record || !$record->practice) {
                                        return [];
                                    }

                                    return \App\Models\InventoryProduct::where('practice_id', $record->practice_id)
                                        ->where('is_active', true)
                                        ->where('stock_quantity', '>', 0)
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->required()
                                ->columnSpan(2)
                                ->reactive(),

                            TextInput::make('quantity')
                                ->label('Qty')
                                ->numeric()
                                ->minValue(1)
                                ->required()
                                ->default(1),
                        ])
                        ->columns(3)
                        ->addable()
                        ->deletable()
                        ->reorderable(false)
                        ->mutateRelationshipDataBeforeCreateUsing(function (array $data, $record): array {
                            if ($record && isset($data['inventory_product_id'])) {
                                $product = \App\Models\InventoryProduct::find($data['inventory_product_id']);
                                if ($product) {
                                    $qty = $data['quantity'] ?? 1;
                                    $data['practice_id'] = $record->practice_id;
                                    $data['description'] = "{$product->name} (x{$qty})";
                                    $data['amount'] = ($product->selling_price ?? 0) * $qty;
                                }
                            }
                            return $data;
                        })
                        ->mutateRelationshipDataBeforeSaveUsing(function (array $data, $record): array {
                            if ($record && isset($data['inventory_product_id'])) {
                                $product = \App\Models\InventoryProduct::find($data['inventory_product_id']);
                                if ($product) {
                                    $qty = $data['quantity'] ?? 1;
                                    $data['practice_id'] = $record->practice_id;
                                    $data['description'] = "{$product->name} (x{$qty})";
                                    $data['amount'] = ($product->selling_price ?? 0) * $qty;
                                }
                            }
                            return $data;
                        }),
                ]),

        ]);
    }
}
