<?php

namespace App\Filament\Resources\LegalForms\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
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
                    ->label('Form Category')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'acupuncture' => 'info',
                        'massage' => 'success',
                        'chiropractic' => 'warning',
                        'physiotherapy' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'general' => 'General',
                        'acupuncture' => 'Acupuncture',
                        'massage' => 'Massage Therapy',
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
                    ->label('Form Category')
                    ->options([
                        'general' => 'General',
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
            ])
            ->emptyStateHeading('No legal forms yet')
            ->emptyStateDescription('Create intake and consent forms required by your practice type.')
            ->emptyStateActions([
                CreateAction::make()->label('New legal form'),
            ]);
    }
}
