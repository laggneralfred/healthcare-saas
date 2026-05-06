<?php

use App\Models\Practice;
use App\Models\User;
use App\Support\PracticeAccessRoles;
use Filament\Auth\Pages\Login;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

require __DIR__.'/../../../vendor/autoload.php';

$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$email = getenv('E2E_ADMIN_EMAIL') ?: 'admin@healthcare.test';
$password = getenv('E2E_ADMIN_PASSWORD') ?: 'password';

PracticeAccessRoles::ensureRoles();

$practice = Practice::query()->firstOrCreate(
    ['slug' => 'playwright-e2e-practice'],
    [
        'name' => 'Playwright E2E Practice',
        'timezone' => 'America/Los_Angeles',
        'is_active' => true,
        'is_demo' => false,
        'trial_ends_at' => now()->addDays(30),
    ],
);

$practice->forceFill([
    'is_active' => true,
    'is_demo' => false,
    'trial_ends_at' => $practice->trial_ends_at && $practice->trial_ends_at->isFuture()
        ? $practice->trial_ends_at
        : now()->addDays(30),
])->save();

$user = User::query()->firstOrNew(['email' => $email]);
$user->forceFill([
    'name' => 'Playwright E2E Admin',
    'practice_id' => $practice->id,
    'email_verified_at' => $user->email_verified_at ?: now(),
]);

if (! $user->exists || ! Hash::check($password, $user->password ?? '')) {
    $user->password = Hash::make($password);
}

$user->save();

$user->assignRole(User::ROLE_ADMINISTRATOR);

RateLimiter::clear('livewire-rate-limiter:'.sha1(Login::class.'|authenticate|127.0.0.1'));
