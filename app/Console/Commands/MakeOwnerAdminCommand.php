<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MakeOwnerAdminCommand extends Command
{
    protected $signature = 'practiq:make-owner-admin
        {email : Email address for the owner/global admin user}
        {--name= : Name to use when creating a new user}
        {--password= : Temporary password to use only when creating a new user}';

    protected $description = 'Create or promote a global owner admin user by setting practice_id to null';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Please provide a valid email address.');

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();
        $created = false;
        $temporaryPassword = null;

        if (! $user) {
            $created = true;
            $temporaryPassword = (string) ($this->option('password') ?: Str::password(16));

            $user = new User([
                'name' => (string) ($this->option('name') ?: Str::before($email, '@')),
                'email' => $email,
                'password' => Hash::make($temporaryPassword),
            ]);
        } elseif ($this->option('password')) {
            $this->warn('Existing user found. Password was not changed.');
        }

        $user->practice_id = null;
        $user->email_verified_at ??= now();
        $user->save();

        $this->info($created ? 'Owner/global admin user created.' : 'Owner/global admin user promoted.');
        $this->line("Email: {$user->email}");
        $this->line('practice_id: null');
        $this->line('Email verified: yes');

        if ($created) {
            $this->line("Temporary password: {$temporaryPassword}");
            $this->warn('Share this temporary password securely and have the owner change it after login.');
        }

        $this->newLine();
        $this->line('Next steps:');
        $this->line('1. Log in at /admin/login with this email.');
        $this->line('2. Open /admin/signedup to view trial signups.');
        $this->line('3. Use the practice switcher when viewing practice-scoped admin pages.');

        return self::SUCCESS;
    }
}
