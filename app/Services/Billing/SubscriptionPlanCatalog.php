<?php

namespace App\Services\Billing;

use App\Models\SubscriptionPlan;
use Illuminate\Support\Collection;

class SubscriptionPlanCatalog
{
    public function plans(): array
    {
        return [
            'solo' => [
                'key' => 'solo',
                'name' => 'Starter',
                'price_monthly' => 4900,
                'max_practitioners' => 1,
                'features' => ['Core clinical tools', '1 Practitioner', 'Basic reporting'],
                'is_active' => true,
                'stripe_price_id' => config('services.stripe.subscription_prices.solo'),
            ],
            'clinic' => [
                'key' => 'clinic',
                'name' => 'Plus',
                'price_monthly' => 9900,
                'max_practitioners' => 5,
                'features' => ['Up to 5 Practitioners', 'Advanced reporting', 'Inventory management'],
                'is_active' => true,
                'stripe_price_id' => config('services.stripe.subscription_prices.clinic'),
            ],
            'enterprise' => [
                'key' => 'enterprise',
                'name' => 'Clinic',
                'price_monthly' => 19900,
                'max_practitioners' => -1,
                'features' => ['Unlimited Practitioners', 'Custom reporting', 'Priority support'],
                'is_active' => true,
                'stripe_price_id' => config('services.stripe.subscription_prices.enterprise'),
            ],
        ];
    }

    public function syncConfiguredPlans(): Collection
    {
        return collect($this->plans())->map(function (array $plan): array {
            $configuredPriceId = $plan['stripe_price_id'] ?: null;

            $payload = $plan;
            unset($payload['key']);

            if ($configuredPriceId === null) {
                unset($payload['stripe_price_id']);
            }

            $subscriptionPlan = SubscriptionPlan::updateOrCreate(
                ['key' => $plan['key']],
                $payload,
            );

            return [
                'key' => $subscriptionPlan->key,
                'name' => $subscriptionPlan->name,
                'stripe_price_id' => $subscriptionPlan->stripe_price_id,
                'configured' => filled($subscriptionPlan->stripe_price_id),
                'source_configured' => filled($configuredPriceId),
            ];
        })->values();
    }

    public function readiness(): array
    {
        $plans = SubscriptionPlan::query()->orderBy('price_monthly')->get();
        $activePlans = $plans->where('is_active', true);

        return [
            'stripe_public_key_configured' => filled(config('services.stripe.public_key')),
            'stripe_secret_configured' => filled(config('services.stripe.secret_key')),
            'stripe_webhook_secret_configured' => filled(config('services.stripe.webhook_secret')),
            'configured_price_ids' => collect($this->plans())
                ->mapWithKeys(fn (array $plan, string $key): array => [$key => filled($plan['stripe_price_id'])])
                ->all(),
            'subscription_plan_rows_exist' => $plans->isNotEmpty(),
            'active_plan_price_ids_present' => $activePlans->isNotEmpty()
                && $activePlans->every(fn (SubscriptionPlan $plan): bool => filled($plan->stripe_price_id)),
            'plans' => $plans,
        ];
    }

    public function mask(?string $value): string
    {
        if (! filled($value)) {
            return 'Missing';
        }

        $value = (string) $value;

        if (strlen($value) <= 12) {
            return substr($value, 0, 4).'...';
        }

        return substr($value, 0, 8).'...'.substr($value, -4);
    }
}
