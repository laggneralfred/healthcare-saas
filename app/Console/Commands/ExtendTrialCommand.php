<?php

namespace App\Console\Commands;

use App\Models\Practice;
use App\Models\User;
use Illuminate\Console\Command;

class ExtendTrialCommand extends Command
{
    protected $signature = 'practiq:extend-trial {practice : Practice slug or user email} {days : Number of days from today}';

    protected $description = 'Extend the trial for a practice by setting trial_ends_at to N days from today';

    public function handle(): int
    {
        $identifier = $this->argument('practice');
        $days       = (int) $this->argument('days');

        if ($days <= 0) {
            $this->error('Days must be a positive integer.');
            return Command::FAILURE;
        }

        // Try by user email first, then by practice slug
        $practice = null;

        if (str_contains($identifier, '@')) {
            $user = User::where('email', $identifier)->first();
            if ($user?->practice_id) {
                $practice = Practice::find($user->practice_id);
            }
        } else {
            $practice = Practice::where('slug', $identifier)->first();
        }

        if (! $practice) {
            $this->error("No practice found for: {$identifier}");
            return Command::FAILURE;
        }

        $newDate = now()->addDays($days);
        $practice->trial_ends_at = $newDate;
        $practice->save();

        $this->info("Trial for [{$practice->name}] extended to {$newDate->toDateString()}.");

        return Command::SUCCESS;
    }
}
