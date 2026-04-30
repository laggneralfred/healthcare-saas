<?php

namespace App\Console\Commands;

use Database\Seeders\LocalFollowUpWorkflowSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\URL;

class SeedLocalFollowUpWorkflowCommand extends Command
{
    protected $signature = 'demo:seed-follow-up-workflow {--base-url=http://127.0.0.1:8002 : Base URL used when printing public request links}';

    protected $description = 'Seed local fake data for the Follow-Up / Invite Back / Appointment Request workflow';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('This command is for local/dev/demo environments only.');

            return self::FAILURE;
        }

        $baseUrl = rtrim((string) $this->option('base-url'), '/');
        config(['app.url' => $baseUrl]);
        URL::forceRootUrl($baseUrl);

        $this->info('Seeding local Follow-Up workflow data...');

        Artisan::call('db:seed', [
            '--class' => LocalFollowUpWorkflowSeeder::class,
            '--force' => true,
        ]);

        $this->line(Artisan::output());

        return self::SUCCESS;
    }
}
