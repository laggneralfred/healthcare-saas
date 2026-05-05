<?php

namespace App\Filament\Resources\Practitioners\RelationManagers;

use App\Models\PractitionerWorkingHour;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WorkingHoursRelationManager extends RelationManager
{
    protected static string $relationship = 'workingHours';

    protected static ?string $title = 'Weekly Working Hours';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('practice_id')
                    ->default(fn () => $this->getOwnerRecord()->practice_id),
                Select::make('day_of_week')
                    ->label('Day')
                    ->options(PractitionerWorkingHour::DAYS)
                    ->required(),
                TextInput::make('start_time')
                    ->type('time')
                    ->required(),
                TextInput::make('end_time')
                    ->type('time')
                    ->required()
                    ->rule('after:start_time'),
                Select::make('is_active')
                    ->label('Active')
                    ->options([
                        true => 'Active',
                        false => 'Inactive',
                    ])
                    ->default(true)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('day_of_week')
            ->columns([
                TextColumn::make('day_of_week')
                    ->label('Day')
                    ->formatStateUsing(fn (int $state): string => PractitionerWorkingHour::DAYS[$state] ?? 'Unknown')
                    ->sortable(),
                TextColumn::make('start_time')
                    ->label('Start'),
                TextColumn::make('end_time')
                    ->label('End'),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(fn (array $data): array => [
                        ...$data,
                        'practice_id' => $this->getOwnerRecord()->practice_id,
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateDataUsing(fn (array $data): array => [
                        ...$data,
                        'practice_id' => $this->getOwnerRecord()->practice_id,
                    ]),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
