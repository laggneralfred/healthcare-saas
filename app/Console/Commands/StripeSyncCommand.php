<?php

namespace App\Console\Commands;

use App\Models\Practice;
use Carbon\Carbon;
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
     * Sync all Stripe subscriptions for a single practice into the local DB.
     * Public so BillingPage (and tests) can call it directly without spawning a
     * separate process.
     *
     * @return array{created: int, updated: int, skipped: int}
     */
    public function syncPractice(Practice $practice): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        try {
            // Retrieve customer with subscriptions expanded in one API call
            $stripeCustomer = $practice->asStripeCustomer(['subscriptions']);
            $stripeSubs     = $stripeCustomer->subscriptions->data ?? [];
        } catch (\Exception $e) {
            if ($this->output->isVerbose()) {
                $this->warn("  Could not reach Stripe for practice #{$practice->id}: {$e->getMessage()}");
            }
            return compact('created', 'updated', 'skipped');
        }

        foreach ($stripeSubs as $stripeSub) {
            $priceId  = $stripeSub->items->data[0]->price->id ?? null;
            $quantity = $stripeSub->items->data[0]->quantity ?? 1;

            // Mirror Cashier's ends_at logic:
            //   - If cancel_at_period_end is set → subscription ends at the period boundary
            //   - If already ended → use ended_at
            //   - Otherwise → null (still active, no end in sight)
            $endsAt = match (true) {
                $stripeSub->cancel_at_period_end => Carbon::createFromTimestamp($stripeSub->current_period_end),
                (bool) $stripeSub->ended_at      => Carbon::createFromTimestamp($stripeSub->ended_at),
                default                          => null,
            };

            $payload = [
                'type'          => 'default',
                'stripe_status' => $stripeSub->status,
                'stripe_price'  => $priceId,
                'quantity'      => $quantity,
                'trial_ends_at' => $stripeSub->trial_end
                    ? Carbon::createFromTimestamp($stripeSub->trial_end)
                    : null,
                'ends_at'       => $endsAt,
            ];

            $existing = $practice->subscriptions()
                ->where('stripe_id', $stripeSub->id)
                ->first();

            if ($existing) {
                if ($existing->stripe_status !== $stripeSub->status
                    || $existing->stripe_price !== $priceId) {
                    $existing->update($payload);
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                $practice->subscriptions()->create(
                    array_merge(['stripe_id' => $stripeSub->id], $payload)
                );
                $created++;
            }
        }

        return compact('created', 'updated', 'skipped');
    }
}
