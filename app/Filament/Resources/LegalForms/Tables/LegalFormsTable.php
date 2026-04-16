<?php

namespace App\Filament\Resources\LegalForms\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LegalFormsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('discipline')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'acupuncture' => 'info',
                        'massage' => 'success',
                        'chiropractic' => 'warning',
                        'physiotherapy' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'acupuncture' => 'Acupuncture',
                        'massage' => 'Massage',
                        'chiropractic' => 'Chiropractic',
                        'physiotherapy' => 'Physiotherapy',
                        default => '—',
                    }),

                TextColumn::make('title'),

                IconColumn::make('is_active')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('discipline')
                    ->options([
                        'acupuncture' => 'Acupuncture',
                        'massage' => 'Massage Therapy',
                        'chiropractic' => 'Chiropractic',
                        'physiotherapy' => 'Physiotherapy',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
