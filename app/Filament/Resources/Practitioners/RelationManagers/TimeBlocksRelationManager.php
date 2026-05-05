<?php

namespace App\Filament\Resources\Practitioners\RelationManagers;

use App\Models\PractitionerTimeBlock;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TimeBlocksRelationManager extends RelationManager
{
    protected static string $relationship = 'timeBlocks';

    protected static ?string $title = 'Time Off / Unavailable Blocks';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('practice_id')
                    ->default(fn () => $this->getOwnerRecord()->practice_id),
                DateTimePicker::make('starts_at')
                    ->required()
                    ->seconds(false),
                DateTimePicker::make('ends_at')
                    ->required()
                    ->seconds(false)
                    ->after('starts_at'),
                Select::make('block_type')
                    ->options(PractitionerTimeBlock::TYPE_OPTIONS)
                    ->nullable(),
                TextInput::make('reason')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reason')
            ->columns([
                TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('block_type')
                    ->formatStateUsing(fn (?string $state): string => $state ? (PractitionerTimeBlock::TYPE_OPTIONS[$state] ?? str($state)->title()) : 'Custom')
                    ->badge(),
                TextColumn::make('reason')
                    ->placeholder('—'),
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
