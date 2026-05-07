<?php

namespace App\Filament\Resources\LegalAcceptances;

use App\Filament\Resources\LegalAcceptances\Pages\ListLegalAcceptances;
use App\Models\LegalAcceptance;
use App\Services\PracticeContext;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LegalAcceptanceResource extends Resource
{
    protected static ?string $model = LegalAcceptance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Legal Acceptances';

    protected static ?string $modelLabel = 'Legal Acceptance';

    protected static ?string $pluralModelLabel = 'Legal Acceptances';

    public static function getEloquentQuery(): Builder
    {
        $query = LegalAcceptance::withoutPracticeScope()
            ->with(['practice', 'user']);

        if (! PracticeContext::isSuperAdmin()) {
            $practiceId = PracticeContext::currentPracticeId();

            return $practiceId
                ? $query->where('practice_id', $practiceId)
                : $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('practice.name')
                    ->label('Practice')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('document_key')
                    ->label('Document')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'terms_of_service' => 'Terms of Service',
                        'privacy_policy' => 'Privacy Policy',
                        'hipaa_baa_acknowledgement' => 'HIPAA / BAA Acknowledgement',
                        'ai_disclaimer_acknowledgement' => 'AI Disclaimer Acknowledgement',
                        default => str($state)->headline()->toString(),
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('document_version')
                    ->label('Version')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Accepted by')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('accepted_at')
                    ->label('Accepted at')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('source')
                    ->label('Source')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('accepted_at', 'desc')
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLegalAcceptances::route('/'),
        ];
    }
}
