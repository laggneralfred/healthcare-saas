<?php

namespace App\Filament\Resources\AppointmentTypes\Schemas;

use App\Models\ServiceFee;
use App\Services\PracticeContext;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AppointmentTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('practice_id')
                    ->default(fn () => auth()->user()->practice_id),

                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->disabledOn('view'),

                TextInput::make('duration_minutes')
                    ->label('Duration')
                    ->numeric()
                    ->minValue(5)
                    ->step(5)
                    ->suffix('minutes')
                    ->required()
                    ->default(60)
                    ->disabledOn('view'),

                Select::make('default_service_fee_id')
                    ->label('Default service fee')
                    ->options(fn (): array => ServiceFee::withoutPracticeScope()
                        ->where('practice_id', PracticeContext::currentPracticeId())
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->placeholder('No default fee')
                    ->disabledOn('view'),

                Toggle::make('is_active')
                    ->default(true)
                    ->disabledOn('view'),
            ]);
    }
}
