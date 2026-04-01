<?php

use App\Jobs\CheckLowStockJob;
use App\Jobs\DispatchAppointmentRemindersJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule low stock checks
Schedule::job(new CheckLowStockJob())
    ->daily()
    ->at('08:00')
    ->name('check-low-stock')
    ->description('Check inventory for low stock and send notifications');

// Dispatch appointment reminders every 15 minutes
Schedule::job(new DispatchAppointmentRemindersJob())
    ->everyFifteenMinutes()
    ->name('dispatch-appointment-reminders')
    ->withoutOverlapping();
