<?php

namespace App\Console\Commands;

use App\Models\Practice;
use App\Services\Billing\StripeSubscriptionSyncService;
use Illuminate\Console\Command;

class StripeSyncCommand extends Command
{
    protected $signature = 'stripe:sync
                            {--practice-id= : Sync only a specific practice by ID (defaults to all)}';

    protected $description = 'Sync subscription data from Stripe into the local subscriptions table';

    public function handle(): int
    {
        $query = Practice::whereNotNull('stripe_id');

        if ($id = $this->option('practice-id')) {
            $query->where('id', $id);
        }

        $practices = $query->get();

        if ($practices->isEmpty()) {
            $this->info('No practices with Stripe customer IDs found.');
            return self::SUCCESS;
        }

        $totalCreated = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;

        foreach ($practices as $practice) {
            $this->line("  Syncing: {$practice->name} (stripe_id: {$practice->stripe_id})");

            $result = $this->syncPractice($practice);

            if (isset($result['error']) && $this->output->isVerbose()) {
                $this->warn("  Could not reach Stripe for practice #{$practice->id}: {$result['error']}");
            }

            $this->line("    → created: {$result['created']}, updated: {$result['updated']}, skipped: {$result['skipped']}");

            $totalCreated += $result['created'];
            $totalUpdated += $result['updated'];
            $totalSkipped += $result['skipped'];
        }

        $this->newLine();
        $this->info("Sync complete — {$totalCreated} created, {$totalUpdated} updated, {$totalSkipped} unchanged.");

        return self::SUCCESS;
    }

    /**
     * Compatibility wrapper for callers that used the command directly.
     * Web requests should prefer StripeSubscriptionSyncService.
     *
     * @return array{created: int, updated: int, skipped: int, error?: string}
     */
    public function syncPractice(Practice $practice): array
    {
        return app(StripeSubscriptionSyncService::class)->syncPractice($practice);
    }
}
