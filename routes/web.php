<?php

use App\Http\Controllers\StripeWebhookController;
use App\Livewire\Public\ConsentForm;
use App\Livewire\Public\IntakeForm;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Stripe webhook — exempt from CSRF, no auth required
// Takes precedence over Cashier's own /stripe/webhook route
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('cashier.webhook');

// Public token-based forms — no authentication required
Route::get('/intake/{token}', IntakeForm::class)->name('intake.show');
Route::get('/consent/{token}', ConsentForm::class)->name('consent.show');
