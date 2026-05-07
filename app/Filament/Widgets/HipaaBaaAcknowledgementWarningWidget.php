<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\HipaaBaaAcknowledgementPage;
use App\Services\LegalAcceptanceService;
use App\Services\PracticeContext;
use Filament\Widgets\Widget;

class HipaaBaaAcknowledgementWarningWidget extends Widget
{
    protected string $view = 'filament.widgets.hipaa-baa-acknowledgement-warning';

    public static function canView(): bool
    {
        $practiceId = PracticeContext::currentPracticeId();

        return $practiceId !== null
            && ! app(LegalAcceptanceService::class)->hasCurrentAcceptance(
                $practiceId,
                LegalAcceptanceService::HIPAA_BAA_ACKNOWLEDGEMENT,
            );
    }

    protected function getViewData(): array
    {
        return [
            'acknowledgementUrl' => HipaaBaaAcknowledgementPage::getUrl(),
        ];
    }
}
