<?php

namespace App\Jobs;

use App\Models\InventoryProduct;
use App\Models\Practice;
use App\Notifications\LowStockNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckLowStockJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get all practices
        $practices = Practice::all();

        foreach ($practices as $practice) {
            // Only check practices with inventory add-on
            if (!$practice->hasInventoryAddon()) {
                continue;
            }

            // Find all low stock products for this practice
            $lowStockProducts = InventoryProduct::where('practice_id', $practice->id)
                ->lowStock()
                ->get();

            if ($lowStockProducts->isEmpty()) {
                continue;
            }

            // Get practice admin user (first user or admin role)
            $adminUser = $practice->users()
                ->first();

            if (!$adminUser) {
                continue;
            }

            // Send notification
            $adminUser->notify(new LowStockNotification($practice, $lowStockProducts->toArray()));
        }
    }
}
