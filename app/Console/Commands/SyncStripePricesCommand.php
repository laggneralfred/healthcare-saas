<?php

namespace App\Console\Commands;

use App\Services\Billing\SubscriptionPlanCatalog;
use Illuminate\Console\Command;

class SyncStripePricesCommand extends Command
{
    protected $signature = 'billing:sync-stripe-prices';

    protected $description = 'Sync configured Stripe subscription price IDs into subscription_plans';

    public function handle(SubscriptionPlanCatalog $catalog): int
    {
        $results = $catalog->syncConfiguredPlans();

        $this->info('Subscription plan Stripe price sync complete.');

        foreach ($results as $result) {
            $status = $result['configured']
                ? 'configured '.$catalog->mask($result['stripe_price_id'])
                : 'missing Stripe price ID';

            $this->line(" - {$result['key']}: {$status}");
        }

        $missing = $results->where('configured', false)->pluck('key')->values();

        if ($missing->isNotEmpty()) {
            $this->warn('Missing price IDs for: '.$missing->implode(', '));
            $this->warn('Set STRIPE_SOLO_PRICE, STRIPE_CLINIC_PRICE, and STRIPE_ENTERPRISE_PRICE as needed, then rerun this command.');
        }

        return self::SUCCESS;
    }
}
