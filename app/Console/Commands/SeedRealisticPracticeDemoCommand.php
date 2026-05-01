<?php

namespace App\Console\Commands;

use Database\Seeders\RealisticPracticeDemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;

class SeedRealisticPracticeDemoCommand extends Command
{
    protected $signature = 'demo:seed-practice-realistic
        {--user=admin@healthcare.test : Practice user whose practice should receive the demo data}
        {--base-url=https://app.practiqapp.com : Base URL used when printing public request links}
        {--reset-demo-data : Reset only records clearly created by this seeder before recreating them}';

    protected $description = 'Seed realistic fake/demo workflow data into the practice owned by a given user';

    public function handle(): int
    {
        $baseUrl = rtrim((string) $this->option('base-url'), '/');
        config(['app.url' => $baseUrl]);
        URL::forceRootUrl($baseUrl);

        $seeder = app(RealisticPracticeDemoSeeder::class)
            ->setContainer(app())
            ->setCommand($this);

        return $seeder->run(
            userEmail: (string) $this->option('user'),
            baseUrl: $baseUrl,
            resetDemoData: (bool) $this->option('reset-demo-data'),
        );
    }
}
