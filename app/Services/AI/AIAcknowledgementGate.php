<?php

namespace App\Services\AI;

use App\Filament\Pages\AiDisclaimerAcknowledgementPage;
use App\Models\Practice;
use App\Services\LegalAcceptanceService;
use App\Services\PracticeContext;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class AIAcknowledgementGate
{
    public function allowsCurrentPractice(): bool
    {
        $practiceId = PracticeContext::currentPracticeId();

        return $practiceId !== null && $this->allowsPractice($practiceId);
    }

    public function allowsPractice(Practice|int $practice): bool
    {
        return app(LegalAcceptanceService::class)->hasAcceptedCurrentVersion(
            $practice,
            LegalAcceptanceService::AI_DISCLAIMER_ACKNOWLEDGEMENT,
        );
    }

    public function ensureAcceptedForCurrentPractice(): bool
    {
        if ($this->allowsCurrentPractice()) {
            return true;
        }

        $this->notifyRequired();

        return false;
    }

    public function ensureAcceptedForPractice(Practice|int $practice): bool
    {
        if ($this->allowsPractice($practice)) {
            return true;
        }

        $this->notifyRequired();

        return false;
    }

    public function notifyRequired(): void
    {
        Notification::make()
            ->title('Please review and acknowledge the AI disclaimer before using AI features.')
            ->warning()
            ->actions([
                Action::make('review_ai_disclaimer')
                    ->label('Review AI Disclaimer')
                    ->url(AiDisclaimerAcknowledgementPage::getUrl()),
            ])
            ->send();
    }
}
