<?php

namespace App\Providers;

use App\Models\Practitioner;
use App\Observers\PractitionerObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Practitioner::observe(PractitionerObserver::class);
    }
}
