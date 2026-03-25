<?php

namespace App\Providers;

use App\Models\Practice;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class CashierServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Cashier::useCustomerModel(Practice::class);
    }
}
