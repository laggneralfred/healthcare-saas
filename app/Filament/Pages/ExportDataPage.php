<?php

namespace App\Filament\Pages;

use App\Jobs\ExportPracticeDataJob;
use App\Models\ExportToken;
use App\Models\Practice;
use App\Services\PracticeContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ExportDataPage extends Page
{
    protected static ?string $slug = 'export-data';

    protected static ?string $title = 'Export Your Data';

    protected static ?string $navigationLabel = 'Exports';
    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 20;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedArrowDownOnSquare;

    protected string $view = 'filament.pages.export-data';

    public function getViewData(): array
    {
        $practice = $this->resolvePractice();

        return [
            'recentExports' => $practice
                ? ExportToken::where('practice_id', $practice->id)
                    ->latest('created_at')
                    ->limit(10)
                    ->get()
                : collect(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label('Export as CSV (ZIP)')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Export as CSV')
                ->modalDescription('Generate a complete ZIP file with all your data as CSV files? This may take a few minutes.')
                ->modalSubmitActionLabel('Export')
                ->action(fn () => $this->requestExport('csv')),

            Action::make('exportJson')
                ->label('Export as JSON')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Export as JSON')
                ->modalDescription('Generate a single JSON file with all your data? This may take a few minutes.')
                ->modalSubmitActionLabel('Export')
                ->action(fn () => $this->requestExport('json')),

            Action::make('exportFinancialCsv')
                ->label('Financial CSV')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->form([
                    DatePicker::make('start_date')
                        ->label('Start date')
                        ->default(now()->startOfMonth())
                        ->required(),
                    DatePicker::make('end_date')
                        ->label('End date')
                        ->default(now()->endOfMonth())
                        ->required()
                        ->afterOrEqual('start_date'),
                ])
                ->modalHeading('Export financial CSVs')
                ->modalDescription('Generate accountant-safe CSV files for collected payments, line items, and a summary. Clinical notes are not included.')
                ->modalSubmitActionLabel('Export')
                ->action(fn (array $data) => $this->requestFinancialExport($data)),
        ];
    }

    public function requestExport(string $format): void
    {
        $practice = $this->resolvePractice();

        if (!$practice) {
            Notification::make()
                ->title('Error')
                ->body('No practice associated with your account.')
                ->danger()
                ->send();
            return;
        }

        // Create token
        $token = ExportToken::create([
            'practice_id' => $practice->id,
            'format' => $format,
            'status' => 'processing',
            'expires_at' => now()->addHours(24),
        ]);

        // Dispatch job
        ExportPracticeDataJob::dispatch($practice->id, $token->id, $format);

        Notification::make()
            ->title('Export started')
            ->body('Your export is being prepared. You will receive an email when it is ready.')
            ->success()
            ->send();
    }

    public function requestFinancialExport(array $data): void
    {
        $practice = $this->resolvePractice();

        if (!$practice) {
            Notification::make()
                ->title('Error')
                ->body('No practice associated with your account.')
                ->danger()
                ->send();
            return;
        }

        $token = ExportToken::create([
            'practice_id' => $practice->id,
            'format' => 'financial_csv',
            'status' => 'processing',
            'expires_at' => now()->addHours(24),
        ]);

        ExportPracticeDataJob::dispatch(
            $practice->id,
            $token->id,
            'financial_csv',
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
        );

        Notification::make()
            ->title('Financial export started')
            ->body('Your financial CSV export is being prepared. You will receive an email when it is ready.')
            ->success()
            ->send();
    }

    private function resolvePractice(): ?Practice
    {
        $practiceId = PracticeContext::currentPracticeId();

        if (!$practiceId) {
            return null;
        }

        return Practice::find($practiceId);
    }
}
