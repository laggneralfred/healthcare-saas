<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ResetDemoCommand extends Command
{
    protected $signature = 'demo:reset';
    protected $description = 'Manually reset the demo data by running the DemoSeeder';

    public function handle(): void
    {
        $this->info('Starting demo data reset...');

        Artisan::call('db:seed', [
            '--class' => 'DemoSeeder',
            '--force' => true,
        ]);

        $this->info(Artisan::output());
        $this->info('Demo data reset completed successfully!');
    }
}
