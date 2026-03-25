<?php

namespace App\Filament\Resources\CheckoutSessions\Schemas;

use App\Models\States\CheckoutSession\Open;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

        ]);
    }
}
