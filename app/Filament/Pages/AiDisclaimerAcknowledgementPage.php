<?php

namespace App\Filament\Pages;

use App\Models\LegalAcceptance;
use App\Models\Practice;
use App\Services\LegalAcceptanceService;
use App\Services\PracticeContext;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class AiDisclaimerAcknowledgementPage extends Page
{
    protected static ?string $slug = 'ai-disclaimer-acknowledgement';

    protected static ?string $title = 'AI Disclaimer';

    protected static ?string $navigationLabel = 'AI Disclaimer';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.ai-disclaimer-acknowledgement';

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

        $legalAcceptanceService->recordAcceptance(
            $practice,
            $user,
            LegalAcceptanceService::AI_DISCLAIMER_ACKNOWLEDGEMENT,
            request(),
            'ai_disclaimer_acknowledgement',
        );

        Notification::make()
            ->title('AI disclaimer acknowledgement recorded.')
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
                LegalAcceptanceService::AI_DISCLAIMER_ACKNOWLEDGEMENT,
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
            'documentVersion' => config('legal.documents.ai_disclaimer_acknowledgement.version'),
            'documentUrl' => config('legal.documents.ai_disclaimer_acknowledgement.url'),
            'latestAcceptance' => $this->latestAcceptance(),
        ];
    }

    private function practice(): ?Practice
    {
        $practiceId = PracticeContext::currentPracticeId();

        return $practiceId ? Practice::query()->find($practiceId) : null;
    }
}
