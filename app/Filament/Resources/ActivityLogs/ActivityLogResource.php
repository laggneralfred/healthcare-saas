<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Models\ActivityLog;
use App\Services\PracticeContext;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Audit Log';

    protected static \UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?int $navigationGroupSort = 100;

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Audit Log Entry';

    protected static ?string $pluralModelLabel = 'Audit Log';

    // ── Access control ─────────────────────────────────────────────────────────

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

    /**
     * Super-admins see all logs (scoped by practice switcher if set).
     * Regular users see only their own practice's logs.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $practiceId = PracticeContext::currentPracticeId();
        if ($practiceId) {
            $query->where(function (Builder $q) use ($practiceId) {
                $q->where('practice_id', $practiceId)
                  ->orWhereNull('practice_id');
            });
        }

        return $query->latest();
    }

    // ── Table ──────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('action')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'viewed'        => 'gray',
                        'created'       => 'success',
                        'updated'       => 'info',
                        'deleted'       => 'danger',
                        'state_changed' => 'warning',
                        'signed'        => 'primary',
                        'exported'      => 'gray',
                        default         => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'viewed'        => 'Viewed',
                        'created'       => 'Created',
                        'updated'       => 'Updated',
                        'deleted'       => 'Deleted',
                        'state_changed' => 'State Changed',
                        'signed'        => 'Signed',
                        'exported'      => 'Exported',
                        default         => $state,
                    }),

                TextColumn::make('auditable_label')
                    ->label('Record')
                    ->searchable(),

                TextColumn::make('auditable_type')
                    ->label('Model')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->toggleable(),

                TextColumn::make('user_email')
                    ->label('User')
                    ->searchable(),

                TextColumn::make('practice.name')
                    ->label('Practice')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->options([
                        'viewed'        => 'Viewed',
                        'created'       => 'Created',
                        'updated'       => 'Updated',
                        'deleted'       => 'Deleted',
                        'state_changed' => 'State Changed',
                        'signed'        => 'Signed',
                        'exported'      => 'Exported',
                    ]),

                SelectFilter::make('auditable_type')
                    ->label('Model')
                    ->options([
                        'App\Models\Patient'               => 'Patient',
                        'App\Models\Appointment'           => 'Appointment',
                        'App\Models\Encounter'             => 'Encounter',
                        'App\Models\AcupunctureEncounter'  => 'Acupuncture Encounter',
                        'App\Models\MedicalHistory'      => 'Intake Submission',
                        'App\Models\ConsentRecord'         => 'Consent Record',
                        'App\Models\CheckoutSession'       => 'Checkout Session',
                        'App\Models\Practitioner'          => 'Practitioner',
                    ]),

                Filter::make('date_range')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('From'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'],  fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']));
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([])
            ->toolbarActions([]);
    }

    // ── Pages ──────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
        ];
    }
}
