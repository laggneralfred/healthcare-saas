<?php

use App\Http\Controllers\Api\PracticeStatsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// API routes for authenticated users
Route::middleware('auth:sanctum')->group(function () {
    // User endpoint
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Practice statistics
    Route::get('/practices/{practice}/stats', [PracticeStatsController::class, 'show'])
        ->name('api.practices.stats');
});
