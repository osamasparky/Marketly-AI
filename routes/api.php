<?php

use App\Domains\Identity\Controllers\AuthController;
use App\Http\Controllers\Api\V1\CspReportController;
use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Marketly-AI API Routes (v1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Health Check Endpoint
    Route::get('/health', [HealthController::class, 'index'])->name('api.v1.health');

    // CSP Violation Reporting
    Route::post('/csp-report', CspReportController::class)->name('api.v1.csp.report');

    // Authentication Endpoints (Rate Limited)
    Route::prefix('auth')->middleware('throttle:15,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('api.v1.auth.register');
        Route::post('/login', [AuthController::class, 'login'])->name('api.v1.auth.login');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
        });
    });

    // Authenticated User Profile
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('api.v1.me');
    });
});
