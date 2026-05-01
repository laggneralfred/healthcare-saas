<?php

namespace App\Console\Commands;

use Database\Seeders\IslandMassageAcupunctureSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;

class SeedIslandMassageAcupunctureCommand extends Command
{
    protected $signature = 'demo:seed-island-massage-acupuncture
        {--admin-email=maria-demo@practiq.local : Login email for the seeded clinic admin}
        {--patient-email=laggneralfred@gmail.com : Email used for normal seeded demo patients}
        {--base-url=https://app.practiqapp.com : Base URL used when printing public request links}
        {--reset-demo-data : Reset only records clearly created by this seeder before recreating them}
        {--demo-mode : Mark the seeded practice as demo/read-only mode}';

    protected $description = 'Seed a realistic fake Island Massage and Acupuncture clinic dataset';

    public function handle(): int
    {
        $baseUrl = rtrim((string) $this->option('base-url'), '/');
        config(['app.url' => $baseUrl]);
        URL::forceRootUrl($baseUrl);

        $seeder = app(IslandMassageAcupunctureSeeder::class)
            ->setContainer(app())
            ->setCommand($this);

        return $seeder->run(
            adminEmail: (string) $this->option('admin-email'),
            patientEmail: (string) $this->option('patient-email'),
            baseUrl: $baseUrl,
            resetDemoData: (bool) $this->option('reset-demo-data'),
            demoMode: (bool) $this->option('demo-mode'),
        );
    }
}
