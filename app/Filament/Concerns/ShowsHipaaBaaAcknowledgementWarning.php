<?php

namespace App\Filament\Concerns;

use App\Filament\Pages\HipaaBaaAcknowledgementPage;
use App\Services\LegalAcceptanceService;
use App\Services\PracticeContext;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

trait ShowsHipaaBaaAcknowledgementWarning
{
    public function getSubheading(): string|Htmlable|null
    {
        $practiceId = PracticeContext::currentPracticeId();

        if (
            $practiceId
            && ! app(LegalAcceptanceService::class)->hasCurrentAcceptance(
                $practiceId,
                LegalAcceptanceService::HIPAA_BAA_ACKNOWLEDGEMENT,
            )
        ) {
            $url = e(HipaaBaaAcknowledgementPage::getUrl());

            return new HtmlString(
                '<span style="display:block;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px 12px;color:#92400e;font-size:13px;line-height:1.45;">'.
                'Before entering real patient or clinical data, please complete the HIPAA/BAA acknowledgement. '.
                '<a href="'.$url.'" style="font-weight:800;color:#b45309;text-decoration:none;">Review acknowledgement</a>'.
                '</span>'
            );
        }

        return $this->subheading;
    }
}
