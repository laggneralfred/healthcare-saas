<?php

namespace App\Filament\Resources\PractitionerReviewSubmissions;

use App\Filament\Resources\PractitionerReviewSubmissions\Pages\ListPractitionerReviewSubmissions;
use App\Filament\Resources\PractitionerReviewSubmissions\Pages\ViewPractitionerReviewSubmission;
use App\Models\PractitionerReviewSubmission;
use App\Services\PracticeContext;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PractitionerReviewSubmissionResource extends Resource
{
    protected static ?string $model = PractitionerReviewSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Practitioner Review Submissions';

    protected static ?string $modelLabel = 'Practitioner Review Submission';

    protected static ?string $pluralModelLabel = 'Practitioner Review Submissions';

    public static function getEloquentQuery(): Builder
    {
        $query = PractitionerReviewSubmission::withoutPracticeScope()
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

                TextColumn::make('user.name')
                    ->label('Submitted by')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('setup_clarity_rating')
                    ->label('Setup clarity')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('scheduling_preference')
                    ->label('Scheduling preference')
                    ->limit(40)
                    ->placeholder('—'),

                IconColumn::make('may_contact')
                    ->label('May contact')
                    ->boolean(),

                IconColumn::make('discount_acknowledged')
                    ->label('Discount acknowledged')
                    ->boolean(),
            ])
            ->defaultSort('submitted_at', 'desc');
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
            'index' => ListPractitionerReviewSubmissions::route('/'),
            'view' => ViewPractitionerReviewSubmission::route('/{record}'),
        ];
    }
}
