<?php

namespace App\Filament\Pages;

use App\Models\Practice;
use App\Models\LegalAcceptance;
use App\Services\LegalAcceptanceService;
use App\Services\PracticeContext;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class HipaaBaaAcknowledgementPage extends Page
{
    protected static ?string $slug = 'hipaa-baa-acknowledgement';

    protected static ?string $title = 'HIPAA / BAA Acknowledgement';

    protected static ?string $navigationLabel = 'HIPAA / BAA Acknowledgement';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.hipaa-baa-acknowledgement';

    public bool $acknowledged = false;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null
            && $user->practice_id !== null
            && $user->canManageOperations();
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    public function submit(LegalAcceptanceService $legalAcceptanceService): void
    {
        $this->validate([
            'acknowledged' => ['accepted'],
        ]);

        $practice = $this->practice();
        $user = auth()->user();

        abort_unless($practice && $user, 403);

        $legalAcceptanceService->acceptCurrent(
            $practice,
            $user,
            LegalAcceptanceService::HIPAA_BAA_ACKNOWLEDGEMENT,
            request(),
            'hipaa_baa_acknowledgement',
        );

        Notification::make()
            ->title('HIPAA / BAA acknowledgement recorded.')
            ->success()
            ->send();

        $this->acknowledged = false;
    }

    public function latestAcceptance(): ?LegalAcceptance
    {
        $practice = $this->practice();

        return $practice
            ? app(LegalAcceptanceService::class)->latestCurrentAcceptance(
                $practice,
                LegalAcceptanceService::HIPAA_BAA_ACKNOWLEDGEMENT,
            )
            : null;
    }

    protected function rules(): array
    {
        return [
            'acknowledged' => ['accepted'],
        ];
    }

    protected function getViewData(): array
    {
        return [
            'practice' => $this->practice(),
            'documentVersion' => config('legal.documents.hipaa_baa_acknowledgement.version'),
            'documentUrl' => config('legal.documents.hipaa_baa_acknowledgement.url'),
            'latestAcceptance' => $this->latestAcceptance(),
        ];
    }

    private function practice(): ?Practice
    {
        $practiceId = PracticeContext::currentPracticeId();

        return $practiceId ? Practice::query()->find($practiceId) : null;
    }
}
