<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ResetDemoDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Log::info('ResetDemoDataJob: Starting demo data reset');
        
        Artisan::call('db:seed', [
            '--class' => 'DemoSeeder',
            '--force' => true,
        ]);

        Log::info('ResetDemoDataJob: Completed demo data reset');
    }
}
