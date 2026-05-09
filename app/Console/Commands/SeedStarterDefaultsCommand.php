<?php

namespace App\Console\Commands;

use App\Models\Practice;
use App\Models\User;
use App\Services\Onboarding\PracticeStarterDefaultsService;
use Illuminate\Console\Command;

class SeedStarterDefaultsCommand extends Command
{
    protected $signature = 'practiq:seed-starter-defaults
        {practice_id : Practice ID to seed}
        {--user= : User email or ID to use for the initial practitioner}';

    protected $description = 'Seed editable starter practitioner, hours, treatment types, and compatibility for a practice';

    public function handle(PracticeStarterDefaultsService $defaults): int
    {
        $practice = Practice::query()->find($this->argument('practice_id'));

        if (! $practice) {
            $this->error('Practice not found.');

            return self::FAILURE;
        }

        $user = $this->resolveUser($practice);

        if (! $user) {
            $this->error('No practice user found. Pass --user=email@example.com or --user=ID.');

            return self::FAILURE;
        }

        $result = $defaults->seed($practice, $user);
        $created = $result['created'];

        $this->info("Starter defaults checked for {$practice->name}.");
        $this->line('Created practitioner: '.($created['practitioner'] ? 'yes' : 'no'));
        $this->line("Created working-hour rows: {$created['working_hours']}");
        $this->line("Created appointment types: {$created['appointment_types']}");
        $this->line("Created service fees: {$created['service_fees']}");
        $this->line("Created practitioner/type links: {$created['compatibilities']}");
        $this->line('Next step: review /onboarding or Settings -> Setup Checklist.');

        return self::SUCCESS;
    }

    private function resolveUser(Practice $practice): ?User
    {
        $identifier = $this->option('user');

        if ($identifier) {
            return User::query()
                ->when(
                    is_numeric($identifier),
                    fn ($query) => $query->whereKey((int) $identifier),
                    fn ($query) => $query->where('email', $identifier),
                )
                ->where('practice_id', $practice->id)
                ->first();
        }

        return User::query()
            ->where('practice_id', $practice->id)
            ->orderBy('id')
            ->first();
    }
}
