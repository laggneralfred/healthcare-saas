<?php

namespace App\Filament\Resources\TrialSignups;

use App\Filament\Resources\TrialSignups\Pages\ListTrialSignups;
use App\Filament\Resources\TrialSignups\Pages\ViewTrialSignup;
use App\Models\TrialSignup;
use App\Services\PracticeContext;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TrialSignupResource extends Resource
{
    protected static ?string $model = TrialSignup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Signed Up';

    protected static ?string $modelLabel = 'Trial Signup';

    protected static ?string $pluralModelLabel = 'Trial Signups';

    public static function getSlug(?Panel $panel = null): string
    {
        return 'signedup';
    }

    public static function canAccess(): bool
    {
        return PracticeContext::isSuperAdmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getEloquentQuery(): Builder
    {
        return TrialSignup::withoutPracticeScope()
            ->with(['practice', 'user']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('signed_up_at')
                    ->label('Signed up')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('practice_name')
                    ->label('Practice name')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('profession')
                    ->label('Profession')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('practice_type')
                    ->label('Practice type')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('practice.name')
                    ->label('Practice')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->placeholder('—'),
            ])
            ->defaultSort('signed_up_at', 'desc')
            ->recordActions([
                ViewAction::make(),
            ])
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
            'index' => ListTrialSignups::route('/'),
            'view' => ViewTrialSignup::route('/{record}'),
        ];
    }
}
