<?php

use App\Livewire\Public\ConsentForm;
use App\Livewire\Public\IntakeForm;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Public token-based forms — no authentication required
Route::get('/intake/{token}', IntakeForm::class)->name('intake.show');
Route::get('/consent/{token}', ConsentForm::class)->name('consent.show');
