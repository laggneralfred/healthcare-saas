<?php

namespace App\Services\Billing;

use App\Models\Practice;

class PracticeFeatureAccess
{
    public const FEATURE_AI = 'ai_features';
    public const FEATURE_ADVANCED_FOLLOW_UP = 'advanced_follow_up';
    public const FEATURE_ADVANCED_AUTOMATION = 'advanced_automation';
    public const FEATURE_MULTI_PRACTITIONER_SCHEDULING = 'multi_practitioner_scheduling';

    public function canUseAiFeatures(?Practice $practice): bool
    {
        return $this->canUseFeature($practice, Practice::PLAN_TIER_PLUS);
    }

    public function canUseAdvancedFollowUp(?Practice $practice): bool
    {
        return $this->canUseFeature($practice, Practice::PLAN_TIER_PLUS);
    }

    public function canUseAdvancedAutomation(?Practice $practice): bool
    {
        return $this->canUseFeature($practice, Practice::PLAN_TIER_CLINIC);
    }

    public function canUseMultiPractitionerScheduling(?Practice $practice): bool
    {
        return $this->canUseFeature($practice, Practice::PLAN_TIER_CLINIC);
    }

    public function teaserCopy(string $feature): array
    {
        return match ($feature) {
            self::FEATURE_AI => [
                'heading' => 'Practiq Plus feature',
                'body' => 'AI drafting is available in Practiq Plus for practitioners who would like additional documentation assistance.',
                'submit_label' => 'Okay',
                'cancel_label' => 'Not now',
            ],
            self::FEATURE_ADVANCED_FOLLOW_UP => [
                'heading' => 'Practiq Plus feature',
                'body' => 'Advanced follow-up tools are available in Practiq Plus.',
                'submit_label' => 'Okay',
                'cancel_label' => 'Not now',
            ],
            self::FEATURE_ADVANCED_AUTOMATION, self::FEATURE_MULTI_PRACTITIONER_SCHEDULING => [
                'heading' => 'Practiq Clinic feature',
                'body' => 'This capability is available in the Practiq Clinic tier for larger teams.',
                'submit_label' => 'Okay',
                'cancel_label' => 'Not now',
            ],
            default => [
                'heading' => 'Feature availability',
                'body' => 'This feature is not available on your current plan.',
                'submit_label' => 'Okay',
                'cancel_label' => 'Not now',
            ],
        };
    }

    public function effectivePlanTier(?Practice $practice): string
    {
        if (! $practice) {
            return Practice::PLAN_TIER_CLINIC;
        }

        $configuredTier = $practice->planTier();

        if ($configuredTier !== Practice::PLAN_TIER_STARTER) {
            return $configuredTier;
        }

        $planKey = $practice->currentPlan()?->key;

        return match ($planKey) {
            'clinic' => Practice::PLAN_TIER_PLUS,
            'enterprise' => Practice::PLAN_TIER_CLINIC,
            default => Practice::PLAN_TIER_STARTER,
        };
    }

    private function canUseFeature(?Practice $practice, string $minimumTier): bool
    {
        if (! $practice) {
            return true;
        }

        return $this->tierRank($this->effectivePlanTier($practice)) >= $this->tierRank($minimumTier);
    }

    private function tierRank(string $tier): int
    {
        return match ($tier) {
            Practice::PLAN_TIER_CLINIC => 3,
            Practice::PLAN_TIER_PLUS => 2,
            default => 1,
        };
    }
}
